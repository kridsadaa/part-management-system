<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูลและฟังก์ชันจัดการไฟล์
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/file_utils.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วและเป็น admin หรือไม่
if (!isLoggedIn() || !isAdmin()) {
    header("Location: access_denied.php");
    exit;
}

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($_POST['file_id']) || empty($_POST['file_id']) || 
    !isset($_POST['part_id']) || empty($_POST['part_id'])) {
    
    setAlert('danger', 'ข้อมูลไม่ครบถ้วน');
    header("Location: index.php");
    exit;
}

$file_id = (int)$_POST['file_id'];
$part_id = (int)$_POST['part_id'];

// ดึงข้อมูลไฟล์
$sql = "SELECT * FROM files WHERE file_id = $file_id";
$result = executeQuery($sql);

if (!$result || !sqlsrv_has_rows($result)) {
    setAlert('danger', 'ไม่พบข้อมูลไฟล์ในระบบ');
    header("Location: part_detail.php?id=$part_id");
    exit;
}

$file = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

// ตรวจสอบว่าไฟล์นี้เป็นของชิ้นส่วนที่ระบุหรือไม่
if ($file['part_id'] != $part_id) {
    setAlert('danger', 'ข้อมูลไม่ถูกต้อง');
    header("Location: part_detail.php?id=$part_id");
    exit;
}

// ลบไฟล์จากระบบไฟล์
$file_path = __DIR__ . '/' . $file['file_path'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// ใช้ Transaction เพื่อให้แน่ใจว่าการลบทั้งหมดจะสำเร็จหรือล้มเหลวพร้อมกัน
sqlsrv_begin_transaction($conn);
$transaction_success = true;

// ลบประวัติการเข้าถึงไฟล์
$sql_delete_logs = "DELETE FROM file_access_logs WHERE file_id = $file_id";
$result_logs = executeQuery($sql_delete_logs);

if (!$result_logs) {
    $transaction_success = false;
    error_log("ไม่สามารถลบประวัติการเข้าถึงไฟล์: " . print_r(sqlsrv_errors(), true));
}

// ลบข้อมูลไฟล์จากฐานข้อมูล
$sql_delete = "DELETE FROM files WHERE file_id = $file_id";
$result_delete = executeQuery($sql_delete);

if (!$result_delete) {
    $transaction_success = false;
    error_log("ไม่สามารถลบข้อมูลไฟล์: " . print_r(sqlsrv_errors(), true));
}

// ยืนยันหรือยกเลิก Transaction
if ($transaction_success) {
    sqlsrv_commit($conn);
    setAlert('success', 'ลบไฟล์เรียบร้อยแล้ว');
} else {
    sqlsrv_rollback($conn);
    setAlert('danger', 'เกิดข้อผิดพลาดในการลบไฟล์');
}

header("Location: part_detail.php?id=$part_id");
exit;
?>