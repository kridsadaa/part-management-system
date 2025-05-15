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
if (!isset($_POST['part_id']) || empty($_POST['part_id']) || 
    !isset($_POST['file_type_id']) || empty($_POST['file_type_id']) || 
    !isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
    
    setAlert('danger', 'ข้อมูลไม่ครบถ้วนหรือไม่มีไฟล์ที่อัพโหลด');
    header("Location: part_detail.php?id=" . $_POST['part_id']);
    exit;
}

$part_id = (int)$_POST['part_id'];
$file_type_id = (int)$_POST['file_type_id'];

// ตรวจสอบว่าชิ้นส่วนมีอยู่จริงหรือไม่
$sql_part = "SELECT * FROM parts WHERE part_id = $part_id";
$result_part = executeQuery($sql_part);

if (!$result_part || !sqlsrv_has_rows($result_part)) {
    setAlert('danger', 'ไม่พบข้อมูลชิ้นส่วนในระบบ');
    header("Location: index.php");
    exit;
}

$part = sqlsrv_fetch_array($result_part, SQLSRV_FETCH_ASSOC);

// ตรวจสอบว่าประเภทไฟล์มีอยู่จริงหรือไม่
$sql_file_type = "SELECT * FROM file_types WHERE file_type_id = $file_type_id";
$result_file_type = executeQuery($sql_file_type);

if (!$result_file_type || !sqlsrv_has_rows($result_file_type)) {
    setAlert('danger', 'ไม่พบข้อมูลประเภทไฟล์ในระบบ');
    header("Location: part_detail.php?id=$part_id");
    exit;
}

$file_type = sqlsrv_fetch_array($result_file_type, SQLSRV_FETCH_ASSOC);

// เริ่ม Transaction สำหรับการอัพโหลดไฟล์
sqlsrv_begin_transaction($conn);
$transaction_success = true;

// ตรวจสอบว่ามีไฟล์ประเภทนี้อยู่แล้วหรือไม่
$sql_check = "SELECT * FROM files WHERE part_id = $part_id AND file_type_id = $file_type_id";
$result_check = executeQuery($sql_check);

if (sqlsrv_has_rows($result_check)) {
    // ถ้ามีไฟล์ประเภทนี้อยู่แล้ว ให้ลบไฟล์เก่าก่อน
    $old_file = sqlsrv_fetch_array($result_check, SQLSRV_FETCH_ASSOC);
    $old_file_path = __DIR__ . '/' . $old_file['file_path'];
    
    if (file_exists($old_file_path)) {
        // ลบไฟล์เก่าจากระบบไฟล์
        if (!unlink($old_file_path)) {
            error_log("ไม่สามารถลบไฟล์เก่า: $old_file_path");
            // แต่ยังสามารถดำเนินการต่อได้
        }
    }
    
    // ลบประวัติการเข้าถึงไฟล์เก่า
    $sql_delete_logs = "DELETE FROM file_access_logs WHERE file_id = " . $old_file['file_id'];
    $result_delete_logs = executeQuery($sql_delete_logs);
    
    if (!$result_delete_logs) {
        $transaction_success = false;
        error_log("ไม่สามารถลบประวัติการเข้าถึงไฟล์เก่า: " . print_r(sqlsrv_errors(), true));
    }
    
    // ลบข้อมูลไฟล์เก่าจากฐานข้อมูล
    $sql_delete = "DELETE FROM files WHERE file_id = " . $old_file['file_id'];
    $result_delete = executeQuery($sql_delete);
    
    if (!$result_delete) {
        $transaction_success = false;
        error_log("ไม่สามารถลบข้อมูลไฟล์เก่า: " . print_r(sqlsrv_errors(), true));
    }
}

// ถ้าการลบไฟล์เก่าสำเร็จหรือไม่มีไฟล์เก่า ให้อัพโหลดไฟล์ใหม่
if ($transaction_success) {
    // อัพโหลดไฟล์
    $result = uploadFile($_FILES['file'], $part_id, $file_type_id);
    
    // เพิ่มข้อมูลดีบัก
    error_log("Upload result: " . print_r($result, true));
    
    if (!$result['success']) {
        $transaction_success = false;
    }
}

// ยืนยันหรือยกเลิก Transaction
if ($transaction_success) {
    sqlsrv_commit($conn);
    setAlert('success', 'อัพโหลดไฟล์เรียบร้อยแล้ว');
} else {
    sqlsrv_rollback($conn);
    
    $error_message = isset($result['message']) ? $result['message'] : 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์';
    
    // เพิ่มข้อมูลเพิ่มเติมถ้ามี
    if (isset($result['error_details'])) {
        $details = $result['error_details'];
        $error_message .= "<br>รายละเอียดเพิ่มเติม: ";
        $error_message .= "<br>- ไฟล์ชั่วคราว: " . $details['tmp_name'];
        $error_message .= "<br>- ไฟล์ชั่วคราวมีอยู่: " . ($details['tmp_exists'] ? 'ใช่' : 'ไม่');
        $error_message .= "<br>- โฟลเดอร์ปลายทางมีสิทธิ์ในการเขียน: " . ($details['target_dir_writable'] ? 'ใช่' : 'ไม่');
        $error_message .= "<br>- รหัสข้อผิดพลาด: " . $details['error_code'];
    }
    
    setAlert('danger', $error_message);
}

header("Location: part_detail.php?id=$part_id");
exit;
?>