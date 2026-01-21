<?php
// ajax/update_mother_profile.php
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
$mother_name = isset($_POST['mother_name']) ? trim($_POST['mother_name']) : '';
$age = isset($_POST['age']) ? intval($_POST['age']) : 0;
$blood_group = isset($_POST['blood_group']) ? trim($_POST['blood_group']) : '';
$nid_number = isset($_POST['nid_number']) ? trim($_POST['nid_number']) : '';
$mobile_number = isset($_POST['mobile_number']) ? trim($_POST['mobile_number']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$delivery_date = isset($_POST['delivery_date']) ? trim($_POST['delivery_date']) : null;
$complication = isset($_POST['complication']) ? trim($_POST['complication']) : '';
$overall_risk = isset($_POST['overall_risk']) ? trim($_POST['overall_risk']) : 'Low';
$pregnancy_weeks = isset($_POST['pregnancy_weeks']) ? intval($_POST['pregnancy_weeks']) : 0;
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

// Validate required fields
if ($mother_id <= 0 || empty($mother_name) || $age <= 0 || empty($mobile_number)) {
    echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
    exit();
}

// Validate age
if ($age < 15 || $age > 50) {
    echo json_encode(['success' => false, 'error' => 'Age must be between 15 and 50']);
    exit();
}

// Update query
$query = "UPDATE pregnancy_registration SET
            mother_name = ?,
            age = ?,
            blood_group = ?,
            nid_number = ?,
            mobile_number = ?,
            address = ?,
            delivery_date = ?,
            complication = ?,
            overall_risk = ?,
            pregnancy_weeks = ?,
            is_active = ?
          WHERE id = ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param(
    "sisssssssiii",
    $mother_name,
    $age,
    $blood_group,
    $nid_number,
    $mobile_number,
    $address,
    $delivery_date,
    $complication,
    $overall_risk,
    $pregnancy_weeks,
    $is_active,
    $mother_id
);

if ($stmt->execute()) {
    // If marked as inactive (delivered), ensure delivery date is set
    if ($is_active == 0 && empty($delivery_date)) {
        $update_date = "UPDATE pregnancy_registration SET delivery_date = CURDATE() WHERE id = ?";
        $stmt2 = $conn->prepare($update_date);
        $stmt2->bind_param("i", $mother_id);
        $stmt2->execute();
        $stmt2->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update profile: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>