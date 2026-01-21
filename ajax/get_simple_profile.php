<?php
// ajax/get_simple_profile.php
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

try {
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
    
    // Get latest ANC record
    $anc_query = "SELECT * FROM anc_checkup WHERE mother_id = ? ORDER BY checkup_date DESC LIMIT 1";
    $anc_stmt = $conn->prepare($anc_query);
    $anc_stmt->bind_param("i", $mother_id);
    $anc_stmt->execute();
    $anc_result = $anc_stmt->get_result();
    $latest_anc = $anc_result->fetch_assoc();
    $anc_stmt->close();
    
    // Get ANC count
    $count_query = "SELECT COUNT(*) as anc_count FROM anc_checkup WHERE mother_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $mother_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $anc_count = $count_result->fetch_assoc()['anc_count'];
    $count_stmt->close();
    
    // Get ANC history (simplified)
    $history_query = "SELECT 
                        ac.*,
                        DATE_FORMAT(ac.checkup_date, '%d %b, %Y') as checkup_date_formatted
                      FROM anc_checkup ac 
                      WHERE ac.mother_id = ? 
                      ORDER BY ac.checkup_date DESC 
                      LIMIT 5";
    
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->bind_param("i", $mother_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $anc_history = [];
    
    while ($row = $history_result->fetch_assoc()) {
        $anc_history[] = $row;
    }
    $history_stmt->close();
    
    echo json_encode([
        'success' => true,
        'mother' => $mother,
        'latest_anc' => $latest_anc,
        'anc_history' => $anc_history,
        'anc_count' => $anc_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}

$conn->close();
?>