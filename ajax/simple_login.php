<?php
// simple_login.php - Bypasses all database checks
session_start();

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple check - accepts admin/1234
    if ($username === 'admin' && $password === '1234') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['logged_in'] = true;
        
        echo "<script>window.location.href = 'index.php';</script>";
        exit();
    } else {
        $error = "Invalid login! Try admin/1234";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Simple Login</title></head>
<body style="font-family: Arial; text-align: center; margin-top: 100px;">
    <h2>Simple Maternity System Login</h2>
    <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <form method="POST" style="display: inline-block; padding: 20px; border: 1px solid #ccc;">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p><strong>Use: admin / 1234</strong></p>
</body>
</html>