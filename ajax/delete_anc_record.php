<?php
// ajax/delete_anc_record.php
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

// Get record ID
$record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;

if ($record_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid record ID']);
    exit();
}

// Delete the record
$query = "DELETE FROM anc_checkup WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'ANC record deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete record: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>