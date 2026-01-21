<?php
// ajax/get_anc_details.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get ANC ID
$anc_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($anc_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ANC ID']);
    exit();
}

// Fetch ANC details
$query = "SELECT 
            ac.*,
            pr.mother_name,
            pr.age,
            pr.mobile_number,
            DATEDIFF(ac.next_checkup_date, ac.checkup_date) as days_to_next_checkup
          FROM anc_checkup ac
          JOIN pregnancy_registration pr ON ac.mother_id = pr.id
          WHERE ac.id = ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $anc_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'ANC record not found']);
    exit();
}

$anc = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'anc' => $anc
]);

$conn->close();
?>