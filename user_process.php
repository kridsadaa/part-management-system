<?php
/**
 * User Process
 * ไฟล์สำหรับการประมวลผลข้อมูลผู้ใช้ (เพิ่ม, แก้ไข, ลบ)
 */

// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูล
require_once 'config/database.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์แอดมิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ตรวจสอบว่ามีการส่งข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบการกระทำ (action)
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'add':
            // เพิ่มผู้ใช้ใหม่
            addUser();
            break;
        case 'edit':
            // แก้ไขข้อมูลผู้ใช้
            editUser();
            break;
        case 'delete':
            // ลบผู้ใช้
            deleteUser();
            break;
        default:
            // กรณีไม่มีการกระทำที่ถูกต้อง
            setAlert('danger', 'การกระทำไม่ถูกต้อง');
            header("Location: user_management.php");
            exit;
    }
} else {
    // กรณีไม่ใช่การส่งข้อมูลแบบ POST
    header("Location: user_management.php");
    exit;
}

/**
 * ฟังก์ชันสำหรับเพิ่มผู้ใช้ใหม่
 */
function addUser() {
    // รับค่าจากฟอร์ม
    $employee_id = isset($_POST['employee_id']) ? escapeString($_POST['employee_id']) : null;
    $username = escapeString($_POST['username']);
    $password = $_POST['password']; // จะถูกเข้ารหัสด้านล่าง
    $first_name = escapeString($_POST['first_name']);
    $last_name = escapeString($_POST['last_name']);
    $department = isset($_POST['department']) ? escapeString($_POST['department']) : null;
    $position = isset($_POST['position']) ? escapeString($_POST['position']) : null;
    $email = escapeString($_POST['email']);
    $role = escapeString($_POST['role']);
    $status = escapeString($_POST['status']);
    
    // ตรวจสอบว่า username ซ้ำหรือไม่
    $check_sql = "SELECT * FROM users WHERE username = '$username'";
    $check_result = executeQuery($check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        setAlert('danger', 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่อผู้ใช้อื่น');
        header("Location: user_management.php");
        exit;
    }
    
    // ตรวจสอบว่า email ซ้ำหรือไม่
    $check_email_sql = "SELECT * FROM users WHERE email = '$email'";
    $check_email_result = executeQuery($check_email_sql);
    
    if (mysqli_num_rows($check_email_result) > 0) {
        setAlert('danger', 'อีเมลนี้มีอยู่ในระบบแล้ว กรุณาใช้อีเมลอื่น');
        header("Location: user_management.php");
        exit;
    }
    
    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // สร้าง SQL สำหรับเพิ่มผู้ใช้
    $sql = "INSERT INTO users (employee_id, username, password, first_name, last_name, department, position, email, role, status, created_at) 
            VALUES ('$employee_id', '$username', '$hashed_password', '$first_name', '$last_name', '$department', '$position', '$email', '$role', '$status', NOW())";
    
    // ดำเนินการคำสั่ง SQL
    if (executeQuery($sql)) {
        setAlert('success', 'เพิ่มผู้ใช้ใหม่เรียบร้อยแล้ว');
    } else {
        setAlert('danger', 'เกิดข้อผิดพลาดในการเพิ่มผู้ใช้: ' . mysqli_error(getConnection()));
    }
    
    // กลับไปยังหน้าจัดการผู้ใช้
    header("Location: user_management.php");
    exit;
}

/**
 * ฟังก์ชันสำหรับแก้ไขข้อมูลผู้ใช้
 */
function editUser() {
    // รับค่าจากฟอร์ม
    $user_id = (int)$_POST['user_id'];
    $employee_id = isset($_POST['employee_id']) ? escapeString($_POST['employee_id']) : null;
    $username = escapeString($_POST['username']);
    $password = $_POST['password']; // อาจจะว่างเปล่าถ้าไม่ต้องการเปลี่ยนรหัสผ่าน
    $first_name = escapeString($_POST['first_name']);
    $last_name = escapeString($_POST['last_name']);
    $department = isset($_POST['department']) ? escapeString($_POST['department']) : null;
    $position = isset($_POST['position']) ? escapeString($_POST['position']) : null;
    $email = escapeString($_POST['email']);
    $role = escapeString($_POST['role']);
    $status = escapeString($_POST['status']);
    
    // ตรวจสอบว่า username ซ้ำหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
    $check_sql = "SELECT * FROM users WHERE username = '$username' AND user_id != $user_id";
    $check_result = executeQuery($check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        setAlert('danger', 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่อผู้ใช้อื่น');
        header("Location: user_management.php");
        exit;
    }
    
    // ตรวจสอบว่า email ซ้ำหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
    $check_email_sql = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id";
    $check_email_result = executeQuery($check_email_sql);
    
    if (mysqli_num_rows($check_email_result) > 0) {
        setAlert('danger', 'อีเมลนี้มีอยู่ในระบบแล้ว กรุณาใช้อีเมลอื่น');
        header("Location: user_management.php");
        exit;
    }
    
    // เตรียม SQL สำหรับอัปเดตข้อมูลผู้ใช้
    $sql = "UPDATE users SET 
            employee_id = '$employee_id',
            username = '$username',
            first_name = '$first_name',
            last_name = '$last_name',
            department = '$department',
            position = '$position',
            email = '$email',
            role = '$role',
            status = '$status',
            updated_at = NOW()";
    
    // ถ้ามีการกรอกรหัสผ่านใหม่ ให้อัปเดตรหัสผ่านด้วย
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = '$hashed_password'";
    }
    
    // เพิ่มเงื่อนไขสำหรับอัปเดตเฉพาะผู้ใช้ที่ต้องการ
    $sql .= " WHERE user_id = $user_id";
    
    // ดำเนินการคำสั่ง SQL
    if (executeQuery($sql)) {
        setAlert('success', 'อัปเดตข้อมูลผู้ใช้เรียบร้อยแล้ว');
    } else {
        setAlert('danger', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลผู้ใช้: ' . mysqli_error(getConnection()));
    }
    
    // กลับไปยังหน้าจัดการผู้ใช้
    header("Location: user_management.php");
    exit;
}

/**
 * ฟังก์ชันสำหรับลบผู้ใช้
 */
function deleteUser() {
    // รับค่า user_id จากฟอร์ม
    $user_id = (int)$_POST['user_id'];
    
    // ตรวจสอบว่าไม่ได้ลบตัวเอง
    if ($user_id == $_SESSION['user_id']) {
        setAlert('danger', 'คุณไม่สามารถลบบัญชีของตัวเองได้');
        header("Location: user_management.php");
        exit;
    }
    
    // สร้าง SQL สำหรับลบผู้ใช้
    $sql = "DELETE FROM users WHERE user_id = $user_id";
    
    // ดำเนินการคำสั่ง SQL
    if (executeQuery($sql)) {
        setAlert('success', 'ลบผู้ใช้เรียบร้อยแล้ว');
    } else {
        setAlert('danger', 'เกิดข้อผิดพลาดในการลบผู้ใช้: ' . mysqli_error(getConnection()));
    }
    
    // กลับไปยังหน้าจัดการผู้ใช้
    header("Location: user_management.php");
    exit;
}
