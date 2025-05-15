<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูลและฟังก์ชันจัดการไฟล์
require_once 'config/database.php';
require_once 'includes/file_utils.php';

// ตรวจสอบว่ามีการส่ง id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$file_id = (int)$_GET['id'];

// ดึงข้อมูลไฟล์
$sql = "SELECT f.*, ft.type_name, p.part_no, c.company_name 
        FROM files f
        JOIN file_types ft ON f.file_type_id = ft.file_type_id
        JOIN parts p ON f.part_id = p.part_id
        JOIN companies c ON p.company_id = c.company_id
        WHERE f.file_id = $file_id";
$result = executeQuery($sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

$file = mysqli_fetch_assoc($result);

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // บันทึกประวัติการเข้าถึงไฟล์
    $user_id = $_SESSION['user_id'];
    $sql_log = "INSERT INTO file_access_logs (file_id, user_id, access_type) 
                VALUES ('$file_id', '$user_id', 'view')";
    executeQuery($sql_log);
}

// กำหนด Content-Type ตามประเภทไฟล์
$file_extension = strtolower($file['file_extension']);
$content_type = getContentType($file_extension);

// ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
$file_path = __DIR__ . '/' . $file['file_path'];
if (!file_exists($file_path)) {
    die("ไม่พบไฟล์ในระบบ");
}

// ส่งไฟล์ให้ผู้ใช้ดู
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
?>
