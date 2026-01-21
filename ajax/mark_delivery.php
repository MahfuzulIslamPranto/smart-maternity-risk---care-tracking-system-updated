<?php
// ajax/mark_delivery.php
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
$delivery_date = isset($_POST['delivery_date']) ? trim($_POST['delivery_date']) : '';
$delivery_type = isset($_POST['delivery_type']) ? trim($_POST['delivery_type']) : '';
$baby_weight = isset($_POST['baby_weight']) ? floatval($_POST['baby_weight']) : 0;
$baby_gender = isset($_POST['baby_gender']) ? trim($_POST['baby_gender']) : '';
$baby_length = isset($_POST['baby_length']) ? floatval($_POST['baby_length']) : null;
$apgar_score = isset($_POST['apgar_score']) ? intval($_POST['apgar_score']) : null;
$complications = isset($_POST['complications']) ? trim($_POST['complications']) : '';
$mother_condition = isset($_POST['mother_condition']) ? trim($_POST['mother_condition']) : '';
$baby_condition = isset($_POST['baby_condition']) ? trim($_POST['baby_condition']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validate required fields
if ($mother_id <= 0 || empty($delivery_date) || empty($delivery_type) || $baby_weight <= 0 || empty($baby_gender) || empty($mother_condition) || empty($baby_condition)) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // First, check if mother exists and is active
    $check_query = "SELECT id, mother_name FROM pregnancy_registration WHERE id = ? AND is_active = 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $mother_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Mother not found or already marked as delivered');
    }
    
    $mother = $check_result->fetch_assoc();
    $check_stmt->close();
    
    // Insert delivery record
    $insert_query = "INSERT INTO delivery_history 
                    (mother_id, delivery_date, delivery_type, baby_weight, baby_gender, 
                     baby_length, apgar_score, complications, mother_condition, baby_condition, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
        "issdsisssss",
        $mother_id,
        $delivery_date,
        $delivery_type,
        $baby_weight,
        $baby_gender,
        $baby_length,
        $apgar_score,
        $complications,
        $mother_condition,
        $baby_condition,
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save delivery record: ' . $stmt->error);
    }
    
    // Update mother status to inactive/delivered
    $update_query = "UPDATE pregnancy_registration 
                     SET is_active = 0, 
                         delivery_date = ? 
                     WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $delivery_date, $mother_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update mother status: ' . $update_stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery marked successfully. Mother status updated to delivered.',
        'mother_name' => $mother['mother_name']
    ]);
    
    $stmt->close();
    $update_stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>