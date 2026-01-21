<?php
// save_delivery.php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mother_id = mysqli_real_escape_string($conn, $_POST['mother_id']);
    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    $delivery_type = mysqli_real_escape_string($conn, $_POST['delivery_type']);
    $baby_weight = mysqli_real_escape_string($conn, $_POST['baby_weight']);
    $baby_gender = mysqli_real_escape_string($conn, $_POST['baby_gender']);
    $complications = mysqli_real_escape_string($conn, $_POST['complications']);
    $mother_condition = mysqli_real_escape_string($conn, $_POST['mother_condition']);
    $baby_condition = 'Healthy'; // Default
    
    // Insert delivery record
    $sql = "INSERT INTO delivery_history 
            (mother_id, delivery_date, delivery_type, baby_weight, baby_gender, 
             complications, mother_condition, baby_condition)
            VALUES 
            ('$mother_id', '$delivery_date', '$delivery_type', '$baby_weight', 
             '$baby_gender', '$complications', '$mother_condition', '$baby_condition')";
    
    if ($conn->query($sql)) {
        // Update mother's status to delivered
        $update_sql = "UPDATE pregnancy_registration 
                      SET is_active = FALSE 
                      WHERE id = '$mother_id'";
        $conn->query($update_sql);
        
        echo json_encode(['success' => true, 'message' => 'Delivery record saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
}
?>