<?php
// test_db.php - Test database connection
echo "<h1>Testing Database Connection</h1>";

// Database credentials
$host = "localhost";
$username = "root";
$password = "";  // Empty for XAMPP
$database = "smart_maternity_db";

// Connect to MySQL
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("❌ MySQL Connection Failed: " . $conn->connect_error);
}

echo "✅ Connected to MySQL<br>";

// Check if database exists
$result = $conn->query("SHOW DATABASES LIKE '$database'");
if ($result->num_rows > 0) {
    echo "✅ Database '$database' exists<br>";
    
    // Select the database
    if ($conn->select_db($database)) {
        echo "✅ Selected database '$database'<br>";
        
        // Check users table
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "✅ Users table exists<br>";
            
            // Check for admin user
            $result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "✅ Admin user found<br>";
                echo "Username: " . $user['username'] . "<br>";
                echo "Password hash: " . $user['password'] . "<br>";
                
                // Test password 1234
                $test_hash = hash('sha256', '1234');
                echo "Hash of '1234': " . $test_hash . "<br>";
                
                if ($user['password'] === $test_hash) {
                    echo "✅ Password '1234' matches!<br>";
                } else {
                    echo "❌ Password doesn't match!<br>";
                }
            } else {
                echo "❌ Admin user not found<br>";
            }
        } else {
            echo "❌ Users table doesn't exist<br>";
        }
    } else {
        echo "❌ Could not select database<br>";
    }
} else {
    echo "❌ Database '$database' doesn't exist<br>";
}

$conn->close();
?>