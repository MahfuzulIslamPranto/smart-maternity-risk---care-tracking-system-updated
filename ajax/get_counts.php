<?php
require_once '../db.php';

$counts = [
    'highRisk' => 0,
    'ancAlerts' => 0,
    'emergencies' => 0,
    'safePregnancies' => 0
];

// High Risk count
$result = $conn->query("SELECT COUNT(*) as count FROM pregnancy_registration WHERE overall_risk = 'High' AND is_active = TRUE");
$counts['highRisk'] = $result->fetch_assoc()['count'];

// ANC Alerts count
$result = $conn->query("SELECT COUNT(DISTINCT pr.id) as count 
                        FROM pregnancy_registration pr 
                        LEFT JOIN anc_checkup ac ON pr.id = ac.mother_id 
                        WHERE (ac.next_checkup_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 8 DAY) 
                        OR pr.next_anc_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 8 DAY))
                        AND pr.is_active = TRUE");
$counts['ancAlerts'] = $result->fetch_assoc()['count'];

// Emergency count
$result = $conn->query("SELECT COUNT(*) as count 
                        FROM pregnancy_registration 
                        WHERE delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                        AND is_active = TRUE");
$counts['emergencies'] = $result->fetch_assoc()['count'];

// Safe Pregnancy count
$result = $conn->query("SELECT COUNT(*) as count 
                        FROM pregnancy_registration 
                        WHERE overall_risk IN ('Low', 'Medium') 
                        AND is_active = TRUE");
$counts['safePregnancies'] = $result->fetch_assoc()['count'];

echo json_encode($counts);
?>