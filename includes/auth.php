<?php
/**
 * Authentication Functions
 * ไฟล์สำหรับฟังก์ชันการตรวจสอบสิทธิ์และการเข้าสู่ระบบ
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * ฟังก์ชันตรวจสอบการเข้าสู่ระบบ
 * @param string $username ชื่อผู้ใช้
 * @param string $password รหัสผ่าน
 * @return array ผลการเข้าสู่ระบบ
 */
function login($username, $password) {
    global $conn;
    
    // ใช้ Parameterized Query เพื่อป้องกัน SQL Injection
    $sql = "SELECT user_id, username, password, first_name, last_name, email, role, status 
            FROM users 
            WHERE username = ?";
    
    $params = array($username);
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL'];
    }
    
    if (!sqlsrv_execute($stmt)) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้'];
    }
    
    if (sqlsrv_has_rows($stmt)) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        // Check if user is active
        if ($user['status'] != 'active') {
            return ['success' => false, 'message' => 'บัญชีผู้ใช้นี้ถูกระงับการใช้งาน'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['logged_in'] = true;
            
            // Record login session
            recordLoginSession($user['user_id']);
            
            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง'];
        }
    } else {
        return ['success' => false, 'message' => 'ไม่พบชื่อผู้ใช้นี้ในระบบ'];
    }
}

/**
 * ฟังก์ชันบันทึกเซสชันการเข้าสู่ระบบ
 * @param int $user_id รหัสผู้ใช้
 * @return int รหัสเซสชัน
 */
function recordLoginSession($user_id) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // ใช้ Parameterized Query
    $sql = "INSERT INTO user_sessions (user_id, ip_address, user_agent) 
            VALUES (?, ?, ?)";
    
    $params = array($user_id, $ip_address, $user_agent);
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    
    if ($stmt && sqlsrv_execute($stmt)) {
        // ดึงค่า IDENTITY ล่าสุด (เทียบเท่ากับ AUTO_INCREMENT ใน MySQL)
        $sql_get_id = "SELECT @@IDENTITY AS session_id";
        $result = executeQuery($sql_get_id);
        
        if ($result) {
            $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
            $session_id = $row['session_id'];
            $_SESSION['session_id'] = $session_id;
            return $session_id;
        }
    }
    
    // กรณีเกิดข้อผิดพลาด
    return 0;
}

/**
 * ฟังก์ชันบันทึกการออกจากระบบ
 * @return bool ผลการออกจากระบบ
 */
function logout() {
    global $conn;
    
    if (isset($_SESSION['session_id'])) {
        $session_id = $_SESSION['session_id'];
        
        // ใช้ Parameterized Query
        $sql = "UPDATE user_sessions 
                SET logout_time = GETDATE(), status = 'logged_out' 
                WHERE session_id = ?";
        
        $params = array($session_id);
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        
        if ($stmt) {
            sqlsrv_execute($stmt);
        }
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    return true;
}

/**
 * ฟังก์ชันตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
 * @return bool ผลการตรวจสอบ
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * ฟังก์ชันตรวจสอบว่าผู้ใช้มีสิทธิ์เป็น admin หรือไม่
 * @return bool ผลการตรวจสอบ
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * ฟังก์ชันตรวจสอบว่าผู้ใช้มีสิทธิ์เป็น staff หรือไม่
 * @return bool ผลการตรวจสอบ
 */
function isStaff() {
    return isLoggedIn() && isset($_SESSION['role']) && ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'admin');
}

/**
 * ฟังก์ชันบันทึกประวัติการเข้าถึงไฟล์
 * @param int $file_id รหัสไฟล์
 * @param string $access_type ประเภทการเข้าถึง (view, download, print)
 * @return bool ผลการบันทึก
 */
function logFileAccess($file_id, $access_type = 'view') {
    global $conn;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // ใช้ Parameterized Query
    $sql = "INSERT INTO file_access_logs (file_id, user_id, access_type, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $params = array($file_id, $user_id, $access_type, $ip_address, $user_agent);
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    
    if ($stmt && sqlsrv_execute($stmt)) {
        return true;
    }
    
    return false;
}

/**
 * ฟังก์ชันเปลี่ยนรหัสผ่าน
 * @param int $user_id รหัสผู้ใช้
 * @param string $current_password รหัสผ่านปัจจุบัน
 * @param string $new_password รหัสผ่านใหม่
 * @return array ผลการเปลี่ยนรหัสผ่าน
 */
function changePassword($user_id, $current_password, $new_password) {
    global $conn;
    
    // ใช้ Parameterized Query
    $sql = "SELECT password FROM users WHERE user_id = ?";
    $params = array($user_id);
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้'];
    }
    
    if (sqlsrv_has_rows($stmt)) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_params = array($hashed_password, $user_id);
            $update_stmt = sqlsrv_prepare($conn, $update_sql, $update_params);
            
            if ($update_stmt && sqlsrv_execute($update_stmt)) {
                return ['success' => true, 'message' => 'รหัสผ่านถูกเปลี่ยนเรียบร้อยแล้ว'];
            } else {
                return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'];
            }
        } else {
            return ['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'];
        }
    } else {
        return ['success' => false, 'message' => 'ไม่พบผู้ใช้นี้ในระบบ'];
    }
}

/**
 * ฟังก์ชันตรวจสอบการเข้าถึงหน้าที่ต้องการสิทธิ์ admin
 * ถ้าไม่ใช่ admin จะเปลี่ยนเส้นทางไปยังหน้า access_denied.php
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: access_denied.php");
        exit;
    }
}

/**
 * ฟังก์ชันตรวจสอบการเข้าถึงหน้าที่ต้องการสิทธิ์ staff หรือ admin
 * ถ้าไม่ใช่ staff หรือ admin จะเปลี่ยนเส้นทางไปยังหน้า access_denied.php
 */
function requireStaff() {
    if (!isStaff()) {
        header("Location: access_denied.php");
        exit;
    }
}

/**
 * ฟังก์ชันตรวจสอบการเข้าสู่ระบบ
 * ถ้ายังไม่ได้เข้าสู่ระบบจะเปลี่ยนเส้นทางไปยังหน้า login.php
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
?>