<?php
// ajax/save_anc_record.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get form data
$mother_id = isset($_POST['mother_id']) ? intval($_POST['mother_id']) : 0;
$checkup_date = isset($_POST['checkup_date']) ? $_POST['checkup_date'] : '';
$bp = isset($_POST['bp']) ? trim($_POST['bp']) : '';
$sugar = isset($_POST['sugar']) ? floatval($_POST['sugar']) : 0;
$hemoglobin = isset($_POST['hemoglobin']) ? floatval($_POST['hemoglobin']) : 0;
$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
$risk_level = isset($_POST['risk_level']) ? trim($_POST['risk_level']) : 'Low';
$next_checkup_date = isset($_POST['next_checkup_date']) ? $_POST['next_checkup_date'] : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validate required fields
if ($mother_id <= 0 || empty($checkup_date) || empty($bp) || $sugar <= 0 || $hemoglobin <= 0 || $weight <= 0 || empty($next_checkup_date)) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
    exit();
}

// Calculate days to next checkup
$checkup_datetime = new DateTime($checkup_date);
$next_datetime = new DateTime($next_checkup_date);
$interval = $checkup_datetime->diff($next_datetime);
$days_to_next = $interval->days;

// Auto-calculate risk if not provided
if ($risk_level === 'auto') {
    // Get mother's age for risk calculation
    $mother_query = "SELECT age FROM pregnancy_registration WHERE id = ?";
    $stmt = $conn->prepare($mother_query);
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $mother_result = $stmt->get_result();
    
    if ($mother_row = $mother_result->fetch_assoc()) {
        $age = $mother_row['age'];
        
        // Calculate risk based on parameters
        $riskScore = 0;
        
        // Age risk
        if ($age < 18 || $age > 35) $riskScore += 20;
        if ($age > 40) $riskScore += 30;
        
        // Blood Pressure risk
        $bp_parts = explode('/', $bp);
        $systolic = isset($bp_parts[0]) ? (int)$bp_parts[0] : 120;
        $diastolic = isset($bp_parts[1]) ? (int)$bp_parts[1] : 80;
        
        if ($systolic > 140 || $diastolic > 90) $riskScore += 25;
        if ($systolic > 160 || $diastolic > 100) $riskScore += 35;
        
        // Sugar level risk
        if ($sugar > 7.0) $riskScore += 20;
        if ($sugar > 8.5) $riskScore += 30;
        
        // Hemoglobin risk
        if ($hemoglobin < 11.0) $riskScore += 15;
        if ($hemoglobin < 10.0) $riskScore += 25;
        
        // Determine risk level
        if ($riskScore >= 60) {
            $risk_level = 'High';
        } elseif ($riskScore >= 30) {
            $risk_level = 'Medium';
        } else {
            $risk_level = 'Low';
        }
    } else {
        $risk_level = 'Low'; // Default if mother not found
    }
    $stmt->close();
}

// Insert ANC record
$insert_query = "INSERT INTO anc_checkup 
                 (mother_id, checkup_date, bp, sugar, hemoglobin, weight, 
                  risk_level, notes, next_checkup_date, days_to_next_checkup)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                 
$stmt = $conn->prepare($insert_query);
$stmt->bind_param(
    "issddssssi",
    $mother_id,
    $checkup_date,
    $bp,
    $sugar,
    $hemoglobin,
    $weight,
    $risk_level,
    $notes,
    $next_checkup_date,
    $days_to_next
);

if ($stmt->execute()) {
    // Update mother's last ANC date
    $update_query = "UPDATE pregnancy_registration 
                     SET last_anc_date = ?, 
                         next_anc_date = ?,
                         blood_pressure = ?,
                         sugar_level = ?,
                         hemoglobin = ?,
                         weight = ?,
                         overall_risk = ?
                     WHERE id = ?";
                     
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param(
        "sssddssi",
        $checkup_date,
        $next_checkup_date,
        $bp,
        $sugar,
        $hemoglobin,
        $weight,
        $risk_level,
        $mother_id
    );
    $update_stmt->execute();
    $update_stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'ANC record saved successfully',
        'record_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save ANC record: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>