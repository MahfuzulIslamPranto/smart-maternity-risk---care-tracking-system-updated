<?php
session_start();
require_once 'db.php';

$sql = "SELECT * FROM users WHERE username = 'admin'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "User found: " . $user['username'] . "<br>";
    echo "Stored hash: " . $user['password'] . "<br>";
    
    // Test password 1234
    $test_hash = hash('sha256', '1234');
    echo "Test hash (1234): " . $test_hash . "<br>";
    
    if ($user['password'] === $test_hash) {
        echo "✅ Password matches!";
    } else {
        echo "❌ Password doesn't match!";
    }
} else {
    echo "No admin user found!";
}
?>