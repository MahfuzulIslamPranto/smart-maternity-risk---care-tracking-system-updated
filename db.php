<?php
// db.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smart_maternity_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to calculate risk based on medical parameters
function calculateRisk($age, $bp, $sugar, $hemoglobin, $weight) {
    $riskScore = 0;
    
    // Age risk
    if ($age < 18 || $age > 35) $riskScore += 20;
    if ($age > 40) $riskScore += 30;
    
    // Blood Pressure risk
    $bp_parts = explode('/', $bp);
    $systolic = isset($bp_parts[0]) ? (int)$bp_parts[0] : 120;
    $diastolic = isset($bp_parts[1]) ? (int)$bp_parts[1] : 80;
    
    if ($systolic > 140 || $diastolic > 90) $riskScore += 25;
    if ($systolic > 160 || $diastolic > 100) $riskScore += 35;
    
    // Sugar level risk
    if ($sugar > 7.0) $riskScore += 20;
    if ($sugar > 8.5) $riskScore += 30;
    
    // Hemoglobin risk
    if ($hemoglobin < 11.0) $riskScore += 15;
    if ($hemoglobin < 10.0) $riskScore += 25;
    
    // Weight risk (BMI calculation - simplified)
    $height = 1.6; // average height in meters
    if ($weight > 0) {
        $bmi = $weight / ($height * $height);
        if ($bmi < 18.5 || $bmi > 25) $riskScore += 10;
        if ($bmi > 30) $riskScore += 20;
    }
    
    // Determine risk level
    if ($riskScore >= 60) return 'High';
    if ($riskScore >= 30) return 'Medium';
    return 'Low';
}

// Function to get days between dates
function daysBetweenDates($date1, $date2) {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    return $d2->diff($d1)->days;
}
?>