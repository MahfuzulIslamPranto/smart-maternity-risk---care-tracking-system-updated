<?php
// ajax/get_mother_profile.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get mother ID
$mother_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($mother_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid mother ID']);
    exit();
}

try {
    // Fetch mother details with enhanced information
    $query = "SELECT 
                pr.*,
                DATEDIFF(CURDATE(), pr.registration_date) as days_registered,
                CASE 
                    WHEN pr.delivery_date IS NULL THEN 'Pregnant'
                    WHEN pr.delivery_date >= CURDATE() THEN 'Due Soon'
                    ELSE 'Delivered'
                END as pregnancy_status,
                (SELECT COUNT(*) FROM anc_checkup WHERE mother_id = pr.id) as anc_count,
                (SELECT MAX(checkup_date) FROM anc_checkup WHERE mother_id = pr.id) as last_anc_date,
                (SELECT MAX(next_checkup_date) FROM anc_checkup WHERE mother_id = pr.id) as next_anc_date
              FROM pregnancy_registration pr 
              WHERE pr.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Mother not found']);
        exit();
    }
    
    $mother = $result->fetch_assoc();
    $stmt->close();
    
    // Calculate pregnancy weeks based on registration date and current weeks
    $reg_date = new DateTime($mother['registration_date']);
    $now = new DateTime();
    $days_passed = $reg_date->diff($now)->days;
    $weeks_passed = floor($days_passed / 7);
    $current_weeks = $mother['pregnancy_weeks'] + $weeks_passed;
    
    // Cap at 40 weeks
    $current_weeks = min(40, $current_weeks);
    $mother['current_weeks'] = $current_weeks;
    
    // Calculate progress percentage
    $mother['progress_percent'] = min(100, round(($current_weeks / 40) * 100));
    
    // Get latest ANC record with complete details
    $anc_query = "SELECT 
                    ac.*,
                    DATEDIFF(ac.next_checkup_date, CURDATE()) as days_to_next
                  FROM anc_checkup ac 
                  WHERE ac.mother_id = ? 
                  ORDER BY ac.checkup_date DESC 
                  LIMIT 1";
    
    $anc_stmt = $conn->prepare($anc_query);
    $anc_stmt->bind_param("i", $mother_id);
    $anc_stmt->execute();
    $anc_result = $anc_stmt->get_result();
    $latest_anc = $anc_result->fetch_assoc();
    $anc_stmt->close();
    
    // Get ANC history (last 5 visits) with formatted dates
    $history_query = "SELECT 
                        ac.*,
                        DATE_FORMAT(ac.checkup_date, '%d %b, %Y') as checkup_date_formatted,
                        DATE_FORMAT(ac.next_checkup_date, '%d %b, %Y') as next_checkup_formatted,
                        DATEDIFF(ac.next_checkup_date, CURDATE()) as days_remaining
                      FROM anc_checkup ac 
                      WHERE ac.mother_id = ? 
                      ORDER BY ac.checkup_date DESC 
                      LIMIT 5";
    
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->bind_param("i", $mother_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $anc_history = [];
    
    while ($row = $history_result->fetch_assoc()) {
        // Determine visit status based on days remaining
        $days_remaining = $row['days_remaining'];
        if ($days_remaining < 0) {
            $row['visit_status'] = 'overdue';
            $row['visit_status_text'] = 'Overdue by ' . abs($days_remaining) . ' days';
        } elseif ($days_remaining == 0) {
            $row['visit_status'] = 'today';
            $row['visit_status_text'] = 'Today';
        } elseif ($days_remaining <= 3) {
            $row['visit_status'] = 'urgent';
            $row['visit_status_text'] = 'In ' . $days_remaining . ' days';
        } elseif ($days_remaining <= 7) {
            $row['visit_status'] = 'soon';
            $row['visit_status_text'] = 'In ' . $days_remaining . ' days';
        } else {
            $row['visit_status'] = 'future';
            $row['visit_status_text'] = 'In ' . $days_remaining . ' days';
        }
        
        $row['risk_class'] = strtolower($row['risk_level']);
        $anc_history[] = $row;
    }
    $history_stmt->close();
    
    // Get delivery info if exists
    $delivery_query = "SELECT 
                        dh.*,
                        DATE_FORMAT(dh.delivery_date, '%d %b, %Y') as delivery_date_formatted,
                        TIMESTAMPDIFF(DAY, dh.delivery_date, CURDATE()) as days_since_delivery
                      FROM delivery_history dh 
                      WHERE dh.mother_id = ? 
                      ORDER BY dh.delivery_date DESC 
                      LIMIT 1";
    
    $delivery_stmt = $conn->prepare($delivery_query);
    $delivery_stmt->bind_param("i", $mother_id);
    $delivery_stmt->execute();
    $delivery_result = $delivery_stmt->get_result();
    $delivery_info = $delivery_result->fetch_assoc();
    $delivery_stmt->close();
    
    // Calculate days to delivery
    $days_to_delivery = null;
    if ($mother['delivery_date']) {
        $delivery_date = new DateTime($mother['delivery_date']);
        $today = new DateTime();
        $interval = $today->diff($delivery_date);
        $days_to_delivery = $interval->days;
        
        // If delivery date is in the past, make it negative
        if ($today > $delivery_date) {
            $days_to_delivery = -$days_to_delivery;
        }
        
        // Determine delivery urgency
        if ($days_to_delivery > 0) {
            if ($days_to_delivery <= 7) {
                $delivery_urgency = 'urgent';
                $delivery_urgency_text = 'Delivery in ' . $days_to_delivery . ' days';
            } elseif ($days_to_delivery <= 30) {
                $delivery_urgency = 'soon';
                $delivery_urgency_text = 'Delivery in ' . $days_to_delivery . ' days';
            } else {
                $delivery_urgency = 'future';
                $delivery_urgency_text = 'Delivery in ' . $days_to_delivery . ' days';
            }
        } elseif ($days_to_delivery < 0) {
            $delivery_urgency = 'overdue';
            $delivery_urgency_text = 'Overdue by ' . abs($days_to_delivery) . ' days';
        } else {
            $delivery_urgency = 'today';
            $delivery_urgency_text = 'Delivery today!';
        }
        
        $mother['delivery_urgency'] = $delivery_urgency;
        $mother['delivery_urgency_text'] = $delivery_urgency_text;
    }
    
    // Get risk factors based on medical parameters
    $risk_factors = [];
    
    if ($latest_anc) {
        // Check BP
        $bp_parts = explode('/', $latest_anc['bp']);
        $systolic = isset($bp_parts[0]) ? (int)$bp_parts[0] : 120;
        $diastolic = isset($bp_parts[1]) ? (int)$bp_parts[1] : 80;
        
        if ($systolic > 140 || $diastolic > 90) {
            $risk_factors[] = 'High Blood Pressure';
        }
        
        // Check Sugar
        if ($latest_anc['sugar'] > 7.0) {
            $risk_factors[] = 'High Sugar Level';
        }
        
        // Check Hemoglobin
        if ($latest_anc['hemoglobin'] < 11.0) {
            $risk_factors[] = 'Low Hemoglobin';
        }
        
        // Check Weight (BMI approximation)
        $height = 1.6; // average height in meters
        if ($latest_anc['weight'] > 0) {
            $bmi = $latest_anc['weight'] / ($height * $height);
            if ($bmi < 18.5) {
                $risk_factors[] = 'Underweight';
            } elseif ($bmi > 25) {
                $risk_factors[] = 'Overweight';
            }
        }
    }
    
    // Check age risk
    if ($mother['age'] < 18 || $mother['age'] > 35) {
        $risk_factors[] = 'Age Risk';
    }
    
    // Check complications
    if (!empty($mother['complication'])) {
        $risk_factors[] = 'Existing Complications';
    }
    
    $mother['risk_factors'] = $risk_factors;
    
    // Format dates for display
    if ($mother['registration_date']) {
        $mother['registration_date_formatted'] = date('d M, Y', strtotime($mother['registration_date']));
    }
    
    if ($mother['delivery_date']) {
        $mother['delivery_date_formatted'] = date('d M, Y', strtotime($mother['delivery_date']));
    }
    
    if ($mother['last_anc_date']) {
        $mother['last_anc_date_formatted'] = date('d M, Y', strtotime($mother['last_anc_date']));
    }
    
    if ($mother['next_anc_date']) {
        $mother['next_anc_date_formatted'] = date('d M, Y', strtotime($mother['next_anc_date']));
    }
    
    echo json_encode([
        'success' => true,
        'mother' => $mother,
        'latest_anc' => $latest_anc,
        'anc_history' => $anc_history,
        'delivery_info' => $delivery_info,
        'anc_count' => $mother['anc_count'],
        'days_to_delivery' => $days_to_delivery,
        'has_delivery' => !empty($delivery_info),
        'risk_factors' => $risk_factors,
        'delivery_urgency' => $mother['delivery_urgency'] ?? null,
        'delivery_urgency_text' => $mother['delivery_urgency_text'] ?? null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>