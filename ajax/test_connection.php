<?php
// test_connection.php
$host = "localhost";
$username = "root";
$password = "";
$database = "smart_maternity_db";

echo "<h1>Testing Database Connection</h1>";

// Test 1: Connect to MySQL
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("❌ MySQL Connection Failed: " . $conn->connect_error);
}
echo "✅ Connected to MySQL<br>";

// Test 2: Check if database exists
$result = $conn->query("SHOW DATABASES LIKE '$database'");
if ($result->num_rows > 0) {
    echo "✅ Database '$database' exists<br>";
    
    // Select the database
    $conn->select_db($database);
    
    // Test 3: Check users table
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Users table exists<br>";
        
        // Test 4: Check admin user
        $result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
        if ($result->num_rows > 0) {
            echo "✅ Admin user exists<br>";
            $user = $result->fetch_assoc();
            echo "Password hash in DB: " . $user['password'] . "<br>";
            echo "Hash of '1234': " . hash('sha256', '1234') . "<br>";
            
            if ($user['password'] === hash('sha256', '1234')) {
                echo "✅ Password '1234' matches!<br>";
            } else {
                echo "❌ Password doesn't match!<br>";
            }
        } else {
            echo "❌ Admin user not found. Creating...<br>";
            $hash = hash('sha256', '1234');
            $sql = "INSERT INTO users (username, password) VALUES ('admin', '$hash')";
            if ($conn->query($sql)) {
                echo "✅ Admin user created!<br>";
            } else {
                echo "❌ Failed to create admin: " . $conn->error . "<br>";
            }
        }
    } else {
        echo "❌ Users table doesn't exist. Creating...<br>";
        $sql = "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'nurse',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "✅ Users table created<br>";
            
            // Create admin user
            $hash = hash('sha256', '1234');
            $sql = "INSERT INTO users (username, password) VALUES ('admin', '$hash')";
            if ($conn->query($sql)) {
                echo "✅ Admin user created (admin/1234)<br>";
            }
        }
    }
} else {
    echo "❌ Database '$database' doesn't exist. Creating...<br>";
    if ($conn->query("CREATE DATABASE $database")) {
        echo "✅ Database created<br>";
        
        // Select and create tables
        $conn->select_db($database);
        
        $sql = "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'nurse',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "✅ Users table created<br>";
            
            // Create admin user
            $hash = hash('sha256', '1234');
            $sql = "INSERT INTO users (username, password) VALUES ('admin', '$hash')";
            if ($conn->query($sql)) {
                echo "✅ Admin user created (admin/1234)<br>";
            }
        }
    }
}

echo "<hr><h3>Quick Login Test:</h3>";
echo '<form action="login.php" method="POST">';
echo 'Username: <input type="text" name="username" value="admin"><br>';
echo 'Password: <input type="password" name="password" value="1234"><br>';
echo '<button type="submit">Test Login</button>';
echo '</form>';
?>