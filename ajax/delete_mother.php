<?php
// ajax/delete_mother.php
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

// Get mother ID
$mother_id = isset($_POST['mother_id']) ? intval($_POST['mother_id']) : 0;

if ($mother_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid mother ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // First, get mother details for response
    $mother_query = "SELECT mother_name FROM pregnancy_registration WHERE id = ?";
    $stmt = $conn->prepare($mother_query);
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $mother_result = $stmt->get_result();
    
    if ($mother_result->num_rows === 0) {
        throw new Exception('Mother not found');
    }
    
    $mother = $mother_result->fetch_assoc();
    $stmt->close();
    
    // Delete delivery history first (if exists)
    $delete_delivery = "DELETE FROM delivery_history WHERE mother_id = ?";
    $stmt = $conn->prepare($delete_delivery);
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete ANC history
    $delete_anc = "DELETE FROM anc_checkup WHERE mother_id = ?";
    $stmt = $conn->prepare($delete_anc);
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $stmt->close();
    
    // Finally, delete the mother record
    $delete_mother = "DELETE FROM pregnancy_registration WHERE id = ?";
    $stmt = $conn->prepare($delete_mother);
    $stmt->bind_param("i", $mother_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Mother record and all related data deleted successfully',
            'mother_name' => $mother['mother_name']
        ]);
    } else {
        throw new Exception('Failed to delete mother record: ' . $conn->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>