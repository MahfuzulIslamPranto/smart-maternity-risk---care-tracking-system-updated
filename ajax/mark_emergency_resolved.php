<?php
// ajax/mark_emergency_resolved.php
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
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($mother_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid mother ID']);
    exit();
}

try {
    // In a real system, you might want to log this action
    // For now, we'll just update the mother's risk level if appropriate
    // Or create a resolution record
    
    $update_query = "UPDATE pregnancy_registration SET overall_risk = 'Medium' WHERE id = ? AND overall_risk = 'High'";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $mother_id);
    
    if ($stmt->execute()) {
        // Log the resolution (create a simple log table if needed)
        $log_query = "INSERT INTO emergency_resolution_log (mother_id, resolved_by, resolution_notes, resolved_at) VALUES (?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iis", $mother_id, $_SESSION['user_id'], $notes);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Emergency case resolved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update emergency status'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>