<?php
// ajax/get_anc_schedule.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Fetch ANC schedule
$query = "SELECT 
            pr.id as mother_id,
            pr.mother_name,
            pr.mobile_number,
            pr.overall_risk,
            ac.checkup_date as last_visit,
            ac.next_checkup_date as next_visit,
            ac.risk_level as anc_risk,
            DATEDIFF(ac.next_checkup_date, CURDATE()) as days_remaining
          FROM anc_checkup ac
          JOIN pregnancy_registration pr ON ac.mother_id = pr.id
          WHERE ac.next_checkup_date IS NOT NULL
          AND pr.is_active = 1
          ORDER BY ac.next_checkup_date ASC";

$result = $conn->query($query);
$schedule = [];

while ($row = $result->fetch_assoc()) {
    $days_remaining = $row['days_remaining'];
    
    // Determine urgency
    if ($days_remaining < 0) {
        $urgency = 'overdue';
        $urgency_text = 'Overdue by ' . abs($days_remaining) . ' days';
    } elseif ($days_remaining == 0) {
        $urgency = 'today';
        $urgency_text = 'Today';
    } elseif ($days_remaining <= 3) {
        $urgency = 'urgent';
        $urgency_text = 'In ' . $days_remaining . ' days';
    } elseif ($days_remaining <= 7) {
        $urgency = 'soon';
        $urgency_text = 'In ' . $days_remaining . ' days';
    } else {
        $urgency = 'future';
        $urgency_text = 'In ' . $days_remaining . ' days';
    }
    
    $row['urgency'] = $urgency;
    $row['urgency_text'] = $urgency_text;
    $row['next_visit_formatted'] = date('d M, Y', strtotime($row['next_visit']));
    $row['last_visit_formatted'] = $row['last_visit'] ? date('d M, Y', strtotime($row['last_visit'])) : 'Never';
    
    $schedule[] = $row;
}

echo json_encode([
    'success' => true,
    'schedule' => $schedule,
    'count' => count($schedule)
]);

$conn->close();
?>