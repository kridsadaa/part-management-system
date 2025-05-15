<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูลและฟังก์ชันจัดการไฟล์
require_once 'config/database.php';
require_once 'includes/auth.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// ตรวจสอบว่ามีการส่ง id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$file_id = (int)$_GET['id'];

// ดึงข้อมูลไฟล์
$sql = "SELECT * FROM files WHERE file_id = $file_id";
$result = executeQuery($sql);

if (!$result || !sqlsrv_has_rows($result)) {
    die("ไม่พบข้อมูลไฟล์ในระบบ");
}

$file = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
$file_path = __DIR__ . '/' . $file['file_path'];

// ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
if (!file_exists($file_path)) {
    die("ไม่พบไฟล์ในระบบ");
}

// บันทึกประวัติการเข้าถึงไฟล์
$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// ใช้ parameterized query ป้องกัน SQL injection
$sql_log = "INSERT INTO file_access_logs (file_id, user_id, access_type, ip_address, user_agent) 
            VALUES (?, ?, 'download', ?, ?)";

$params = array($file_id, $user_id, $ip_address, $user_agent);
$stmt = sqlsrv_prepare($conn, $sql_log, $params);

if ($stmt) {
    sqlsrv_execute($stmt);
} else {
    // บันทึกข้อผิดพลาดไว้ตรวจสอบ (อาจแสดงให้ผู้ใช้เห็นหรือไม่ก็ได้)
    error_log("ไม่สามารถบันทึกประวัติการดาวน์โหลดไฟล์: " . print_r(sqlsrv_errors(), true));
}

// ส่งไฟล์ให้ผู้ใช้ดาวน์โหลด
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
?>