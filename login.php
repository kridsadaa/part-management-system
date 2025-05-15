<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - I-WiS</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="login-page">
    <?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is already logged in
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        header("Location: index.php");
        exit;
    }
    ?>
    <div class="login-container">
        <div class="login-box">
            <img src="images/logo.png" alt="INFINITY PART Logo" class="login-logo">
            <h1>เข้าสู่ระบบ</h1>
            
            <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['login_error']; 
                unset($_SESSION['login_error']);
                ?>
            </div>
            <?php endif; ?>
            
            <form action="login_process.php" method="POST" class="login-form">
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
                </div>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" placeholder="รหัสผ่าน" required>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">ลืมรหัสผ่าน?</a>
                </div>
                <button type="submit" class="login-button">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>
</body>
</html>
