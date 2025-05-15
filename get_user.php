<?php
/**
 * Get User
 * ไฟล์สำหรับดึงข้อมูลผู้ใช้เพื่อการแก้ไข
 */

// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูล
require_once 'config/database.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์แอดมิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    // ส่งค่ากลับเป็น JSON ว่าไม่มีสิทธิ์
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุ ID ผู้ใช้']);
    exit;
}

// รับค่า ID และทำความสะอาด
$user_id = (int)$_GET['id'];

// ใช้ Parameterized Query เพื่อป้องกัน SQL Injection
$sql = "SELECT * FROM users WHERE user_id = ?";
$params = array($user_id);
$stmt = sqlsrv_prepare($conn, $sql, $params);

if (!$stmt) {
    // กรณีเกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL']);
    exit;
}

// ดึงข้อมูลผู้ใช้
$result = sqlsrv_execute($stmt);

if (!$result) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . print_r(sqlsrv_errors(), true)]);
    exit;
}

// ตรวจสอบว่าพบข้อมูลหรือไม่
if (sqlsrv_has_rows($stmt)) {
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    // จัดการกับค่าวันที่จาก MSSQL
    if (isset($user['created_at']) && $user['created_at'] instanceof DateTime) {
        $user['created_at'] = $user['created_at']->format('Y-m-d H:i:s');
    }
    
    if (isset($user['updated_at']) && $user['updated_at'] instanceof DateTime) {
        $user['updated_at'] = $user['updated_at']->format('Y-m-d H:i:s');
    }
    
    // ลบรหัสผ่านออกจากข้อมูลที่จะส่งกลับ
    unset($user['password']);
    
    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    // กรณีไม่พบข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้']);
}
?>