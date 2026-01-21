<?php
require_once '../db.php';

$data = [
    'high' => 0,
    'medium' => 0,
    'low' => 0
];

$result = $conn->query("SELECT overall_risk, COUNT(*) as count 
                        FROM pregnancy_registration 
                        WHERE is_active = TRUE 
                        GROUP BY overall_risk");
while($row = $result->fetch_assoc()) {
    $risk = strtolower($row['overall_risk']);
    if (isset($data[$risk])) {
        $data[$risk] = $row['count'];
    }
}

echo json_encode($data);
?>