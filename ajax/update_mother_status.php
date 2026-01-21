<?php
// ajax/update_mother_status.php
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

// Get parameters
$mother_id = isset($_POST['mother_id']) ? intval($_POST['mother_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($mother_id <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    switch ($action) {
        case 'deactivate':
            $query = "UPDATE pregnancy_registration SET is_active = 0 WHERE id = ?";
            $message = "Mother deactivated successfully";
            break;
            
        case 'activate':
            $query = "UPDATE pregnancy_registration SET is_active = 1 WHERE id = ?";
            $message = "Mother activated successfully";
            break;
            
        case 'mark_delivered':
            $query = "UPDATE pregnancy_registration SET is_active = 0, delivery_date = CURDATE() WHERE id = ?";
            $message = "Marked as delivered successfully";
            break;
            
        case 'reschedule_anc':
            $new_date = isset($_POST['new_date']) ? $_POST['new_date'] : '';
            if (empty($new_date)) {
                throw new Exception('New date is required');
            }
            
            // Update next ANC date
            $query = "UPDATE anc_checkup SET next_checkup_date = ? WHERE mother_id = ? ORDER BY checkup_date DESC LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $new_date, $mother_id);
            
            if ($stmt->execute()) {
                // Also update mother's next_anc_date
                $update_mother = "UPDATE pregnancy_registration SET next_anc_date = ? WHERE id = ?";
                $stmt2 = $conn->prepare($update_mother);
                $stmt2->bind_param("si", $new_date, $mother_id);
                $stmt2->execute();
                $stmt2->close();
                
                echo json_encode(['success' => true, 'message' => 'ANC rescheduled successfully']);
            } else {
                throw new Exception('Failed to reschedule ANC');
            }
            $stmt->close();
            exit();
            
        default:
            throw new Exception('Invalid action');
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mother_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>