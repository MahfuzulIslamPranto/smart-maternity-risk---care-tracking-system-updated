<?php
// ajax/get_mother_details.php
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

// Fetch mother details
$query = "SELECT * FROM pregnancy_registration WHERE id = ?";
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

// Get latest ANC info
$anc_query = "SELECT * FROM anc_checkup WHERE mother_id = ? ORDER BY checkup_date DESC LIMIT 1";
$anc_stmt = $conn->prepare($anc_query);
$anc_stmt->bind_param("i", $mother_id);
$anc_stmt->execute();
$anc_result = $anc_stmt->get_result();
$latest_anc = $anc_result->fetch_assoc();
$anc_stmt->close();

// Get delivery info if exists
$delivery_query = "SELECT * FROM delivery_history WHERE mother_id = ?";
$delivery_stmt = $conn->prepare($delivery_query);
$delivery_stmt->bind_param("i", $mother_id);
$delivery_stmt->execute();
$delivery_result = $delivery_stmt->get_result();
$delivery_info = $delivery_result->fetch_assoc();
$delivery_stmt->close();

echo json_encode([
    'success' => true,
    'mother_name' => $mother['mother_name'],
    'age' => $mother['age'],
    'blood_group' => $mother['blood_group'],
    'nid_number' => $mother['nid_number'],
    'mobile_number' => $mother['mobile_number'],
    'address' => $mother['address'],
    'overall_risk' => $mother['overall_risk'],
    'is_active' => $mother['is_active'],
    'pregnancy_weeks' => $mother['pregnancy_weeks'],
    'delivery_date' => $mother['delivery_date'],
    'registration_date' => $mother['registration_date'],
    'last_anc_date' => $mother['last_anc_date'],
    'next_anc_date' => $mother['next_anc_date'],
    'latest_bp' => $latest_anc['bp'] ?? null,
    'latest_sugar' => $latest_anc['sugar'] ?? null,
    'latest_hemoglobin' => $latest_anc['hemoglobin'] ?? null,
    'latest_weight' => $latest_anc['weight'] ?? null,
    'has_delivery' => $delivery_info ? true : false
]);

$conn->close();
?>