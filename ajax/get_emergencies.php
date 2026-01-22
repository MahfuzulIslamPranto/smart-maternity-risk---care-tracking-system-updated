<?php
// ajax/get_emergencies.php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Fetch emergency cases
    $query = "SELECT 
        pr.*,
        ac.bp,
        ac.sugar,
        ac.hemoglobin,
        ac.risk_level as anc_risk,
        ac.next_checkup_date,
        DATEDIFF(pr.delivery_date, CURDATE()) as days_to_delivery
    FROM pregnancy_registration pr
    LEFT JOIN (
        SELECT * FROM anc_checkup 
        WHERE (mother_id, checkup_date) IN (
            SELECT mother_id, MAX(checkup_date) 
            FROM anc_checkup 
            GROUP BY mother_id
        )
    ) ac ON pr.id = ac.mother_id
    WHERE pr.is_active = TRUE
    AND (
        pr.overall_risk = 'High' 
        OR pr.delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        OR (ac.bp LIKE '150/%' OR ac.bp LIKE '160/%')
        OR ac.sugar > 8.0
        OR ac.hemoglobin < 10.0
        OR (ac.next_checkup_date < CURDATE() AND pr.overall_risk = 'High')
    )
    ORDER BY 
        CASE 
            WHEN pr.delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 1
            WHEN pr.overall_risk = 'High' THEN 2
            ELSE 3
        END,
        pr.delivery_date ASC";
    
    $result = $conn->query($query);
    $emergencies = [];
    
    while ($row = $result->fetch_assoc()) {
        $emergency_type = [];
        $urgency_level = '';
        
        // Determine emergency type
        if ($row['overall_risk'] === 'High') {
            $emergency_type[] = '🚨 High Risk';
            $urgency_level = 'critical';
        }
        
        if ($row['days_to_delivery'] >= 0 && $row['days_to_delivery'] <= 7) {
            $emergency_type[] = '📅 Due in ' . $row['days_to_delivery'] . ' days';
            $urgency_level = $row['days_to_delivery'] <= 3 ? 'critical' : 'high';
        }
        
        if ($row['bp'] && (strpos($row['bp'], '150/') !== false || strpos($row['bp'], '160/') !== false)) {
            $emergency_type[] = '🩺 High BP';
            $urgency_level = 'critical';
        }
        
        if ($row['sugar'] > 8.0) {
            $emergency_type[] = '🍬 High Sugar';
            if ($row['sugar'] > 9.0) $urgency_level = 'critical';
        }
        
        if ($row['hemoglobin'] < 10.0) {
            $emergency_type[] = '🩸 Low Hb';
            if ($row['hemoglobin'] < 9.0) $urgency_level = 'critical';
        }
        
        // Default urgency if not set
        if (!$urgency_level) {
            $urgency_level = $row['overall_risk'] === 'High' ? 'high' : 'medium';
        }
        
        $row['emergency_types'] = $emergency_type;
        $row['urgency_level'] = $urgency_level;
        $emergencies[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'emergencies' => $emergencies,
        'count' => count($emergencies)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>