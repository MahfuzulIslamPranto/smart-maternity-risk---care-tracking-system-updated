<?php
// login.php - Database login system
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Include database
        require_once 'db.php';
        
        // Fetch user from database
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password (SHA256 hash)
                if ($user['password'] === hash('sha256', $password)) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Redirect to index.php
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Wrong password! Try: admin / 1234";
                }
            } else {
                $error = "User not found! Try: admin / 1234";
            }
            
            $stmt->close();
        } else {
            $error = "Database error. Please check setup.";
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Maternity Login</title>
    <style>
        /* Add some basic styles if style1.css is missing */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            display: flex;
            width: 900px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .login-left {
            flex: 1;
            padding: 50px;
        }
        .login-right {
            flex: 1;
            background: linear-gradient(135deg, #4a6fa5 0%, #166088 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-right img {
            max-width: 80%;
            height: auto;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        input {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #4a6fa5;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4a6fa5 0%, #166088 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .error-message {
            background: #ffeaea;
            color: #e74c3c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .success-message {
            background: #e6f7ed;
            color: #27ae60;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .demo-info {
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
            font-size: 14px;
            color: #166088;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left side -->
        <div class="login-left">
            <h1>Smart Maternity Risk & Care Tracking System</h1>
            <p>Secure access to predictive maternity care portal</p>
            
            <!-- Show messages -->
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Simple working form -->
            <form method="POST" action="">
                <input type="text" name="username" placeholder="Username" required value="admin">
                <input type="password" name="password" placeholder="Password" required value="1234">
                <button type="submit" id="loginBtn">Login</button>
            </form>
            
            <div class="demo-info">
                <strong>Demo Credentials:</strong><br>
                Username: <strong>admin</strong><br>
                Password: <strong>1234</strong>
            </div>
            
            <?php if (isset($_GET['debug'])): ?>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Session ID: <?php echo session_id(); ?><br>
                Form Method: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
                Post Data: <?php echo isset($_POST['username']) ? 'Received' : 'Not received'; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right side -->
        <div class="login-right">
            <img src="images/nurse2.png" alt="Nurse Illustration" onerror="this.style.display='none'">
        </div>
    </div>
    
    <script>
    // Simple form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const button = document.getElementById('loginBtn');
        
        if (form) {
            form.addEventListener('submit', function() {
                button.innerHTML = "Logging in...";
                button.disabled = true;
                return true;
            });
        }
        
        // Auto-focus username field
        const usernameField = document.querySelector('input[name="username"]');
        if (usernameField) {
            usernameField.focus();
        }
    });
    </script>
</body>
</html>