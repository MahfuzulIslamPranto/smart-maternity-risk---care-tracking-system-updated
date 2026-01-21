<?php
// ajax/get_anc_history.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get filter parameters
$mother_id = isset($_GET['mother_id']) ? intval($_GET['mother_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT 
            ac.*,
            pr.mother_name,
            pr.mobile_number,
            pr.age,
            DATEDIFF(ac.next_checkup_date, CURDATE()) as days_until_next
          FROM anc_checkup ac
          JOIN pregnancy_registration pr ON ac.mother_id = pr.id
          WHERE pr.is_active = TRUE";
          
$params = [];
$types = "";

if ($mother_id > 0) {
    $query .= " AND ac.mother_id = ?";
    $params[] = $mother_id;
    $types .= "i";
}

if (!empty($date_from)) {
    $query .= " AND ac.checkup_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND ac.checkup_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY ac.checkup_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$anc_history = [];
while ($row = $result->fetch_assoc()) {
    // Format dates
    $row['checkup_date_formatted'] = date('d M, Y', strtotime($row['checkup_date']));
    $row['next_checkup_formatted'] = date('d M, Y', strtotime($row['next_checkup_date']));
    
    // Determine risk color class
    $row['risk_class'] = strtolower($row['risk_level']);
    
    // Determine if next visit is upcoming
    $days_until_next = $row['days_until_next'];
    if ($days_until_next < 0) {
        $row['visit_status'] = 'overdue';
        $row['visit_status_text'] = 'Overdue by ' . abs($days_until_next) . ' days';
    } elseif ($days_until_next <= 7) {
        $row['visit_status'] = 'urgent';
        $row['visit_status_text'] = 'In ' . $days_until_next . ' days';
    } else {
        $row['visit_status'] = 'upcoming';
        $row['visit_status_text'] = 'In ' . $days_until_next . ' days';
    }
    
    $anc_history[] = $row;
}

// If no mother_id specified, get all records
if (empty($anc_history) && $mother_id == 0) {
    // Get all ANC records for active mothers
    $all_query = "SELECT 
                    ac.*,
                    pr.mother_name,
                    pr.mobile_number,
                    pr.age,
                    DATEDIFF(ac.next_checkup_date, CURDATE()) as days_until_next
                  FROM anc_checkup ac
                  JOIN pregnancy_registration pr ON ac.mother_id = pr.id
                  WHERE pr.is_active = TRUE
                  ORDER BY ac.checkup_date DESC
                  LIMIT 50";
    
    $all_result = $conn->query($all_query);
    
    while ($row = $all_result->fetch_assoc()) {
        // Format dates
        $row['checkup_date_formatted'] = date('d M, Y', strtotime($row['checkup_date']));
        $row['next_checkup_formatted'] = date('d M, Y', strtotime($row['next_checkup_date']));
        
        // Determine risk color class
        $row['risk_class'] = strtolower($row['risk_level']);
        
        // Determine if next visit is upcoming
        $days_until_next = $row['days_until_next'];
        if ($days_until_next < 0) {
            $row['visit_status'] = 'overdue';
            $row['visit_status_text'] = 'Overdue by ' . abs($days_until_next) . ' days';
        } elseif ($days_until_next <= 7) {
            $row['visit_status'] = 'urgent';
            $row['visit_status_text'] = 'In ' . $days_until_next . ' days';
        } else {
            $row['visit_status'] = 'upcoming';
            $row['visit_status_text'] = 'In ' . $days_until_next . ' days';
        }
        
        $anc_history[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $anc_history,
    'count' => count($anc_history)
]);

$stmt->close();
$conn->close();
?>