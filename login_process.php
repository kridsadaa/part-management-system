<?php
/**
 * Login Process
 * ไฟล์สำหรับประมวลผลการเข้าสู่ระบบ
 */

// Include authentication functions
require_once 'includes/auth.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password from form
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "กรุณากรอกชื่อผู้ใช้";
    }
    
    if (empty($password)) {
        $errors[] = "กรุณากรอกรหัสผ่าน";
    }
    
    // If no errors, attempt to login
    if (empty($errors)) {
        $login_result = login($username, $password);
        
        if ($login_result['success']) {
            // บันทึกการเข้าสู่ระบบลงในตาราง user_sessions
            if (isset($_SESSION['user_id'])) {
                // ใช้ Parameterized Query เพื่อบันทึกการเข้าสู่ระบบ
                $user_id = $_SESSION['user_id'];
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                require_once 'config/database.php';
                
                $sql = "INSERT INTO user_sessions (user_id, ip_address, user_agent, status) 
                        VALUES (?, ?, ?, 'active')";
                
                $params = array($user_id, $ip_address, $user_agent);
                $stmt = sqlsrv_prepare($conn, $sql, $params);
                
                if ($stmt) {
                    sqlsrv_execute($stmt);
                    // บันทึก session_id จากฐานข้อมูลเพื่อใช้อ้างอิง
                    $sql_get_id = "SELECT @@IDENTITY as session_id";
                    $result = executeQuery($sql_get_id);
                    
                    if ($result) {
                        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
                        if ($row) {
                            $_SESSION['session_db_id'] = $row['session_id'];
                        }
                    }
                }
            }
            
            // Redirect to index page
            header("Location: index.php");
            exit;
        } else {
            // Set error message
            $_SESSION['login_error'] = $login_result['message'];
            header("Location: login.php");
            exit;
        }
    } else {
        // Set error messages
        $_SESSION['login_error'] = implode("<br>", $errors);
        header("Location: login.php");
        exit;
    }
} else {
    // If not submitted via POST, redirect to login page
    header("Location: login.php");
    exit;
}
?>