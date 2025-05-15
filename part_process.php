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

// ดึงข้อมูลบริษัท
$company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
if ($company_id <= 0) {
    setAlert('danger', 'ไม่พบข้อมูลบริษัท');
    header("Location: index.php");
    exit;
}

// ตรวจสอบการกระทำ
$action = isset($_POST['action']) ? $_POST['action'] : '';

// ดำเนินการตามการกระทำ
switch ($action) {
    case 'add':
        addPart($company_id);
        break;
    case 'edit':
        editPart($company_id);
        break;
    case 'delete':
        deletePart($company_id);
        break;
    default:
        setAlert('danger', 'การกระทำไม่ถูกต้อง');
        header("Location: company.php?id=$company_id");
        exit;
}

/**
 * ฟังก์ชันเพิ่มชิ้นส่วนใหม่
 * @param int $company_id รหัสบริษัท
 */
function addPart($company_id) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['part_no']) || empty($_POST['part_no'])) {
        setAlert('danger', 'กรุณาระบุรหัสชิ้นส่วน');
        header("Location: company.php?id=$company_id");
        exit;
    }
    
    $part_no = escapeString($_POST['part_no']);
    $notes = isset($_POST['notes']) ? escapeString($_POST['notes']) : '';
    $user_id = $_SESSION['user_id'];
    
    // ตรวจสอบว่ารหัสชิ้นส่วนซ้ำหรือไม่
    $sql_check = "SELECT part_id FROM parts WHERE part_no = '$part_no' AND company_id = '$company_id'";
    $result_check = executeQuery($sql_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        setAlert('danger', 'รหัสชิ้นส่วนนี้มีอยู่แล้วในระบบ');
        header("Location: company.php?id=$company_id");
        exit;
    }
    
    // เพิ่มข้อมูลชิ้นส่วนลงฐานข้อมูล
    $sql = "INSERT INTO parts (part_no, company_id, notes, created_by) 
            VALUES ('$part_no', '$company_id', '$notes', '$user_id')";
    
    if (executeQuery($sql)) {
        $part_id = getLastInsertId();
        
        // อัพโหลดไฟล์
        uploadPartFiles($part_id);
        
        setAlert('success', 'เพิ่มชิ้นส่วนเรียบร้อยแล้ว');
    } else {
        setAlert('danger', 'เกิดข้อผิดพลาดในการเพิ่มชิ้นส่วน');
    }
    
    header("Location: company.php?id=$company_id");
    exit;
}

/**
 * ฟังก์ชันแก้ไขชิ้นส่วน
 * @param int $company_id รหัสบริษัท
 */
function editPart($company_id) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['part_id']) || empty($_POST['part_id']) || 
        !isset($_POST['part_no']) || empty($_POST['part_no'])) {
        setAlert('danger', 'ข้อมูลไม่ครบถ้วน');
        header("Location: company.php?id=$company_id");
        exit;
    }
    
    $part_id = (int)$_POST['part_id'];
    $part_no = escapeString($_POST['part_no']);
    $notes = isset($_POST['notes']) ? escapeString($_POST['notes']) : '';
    
    // ตรวจสอบว่ารหัสชิ้นส่วนซ้ำหรือไม่ (ยกเว้นชิ้นส่วนปัจจุบัน)
    $sql_check = "SELECT part_id FROM parts 
                  WHERE part_no = '$part_no' AND company_id = '$company_id' AND part_id != '$part_id'";
    $result_check = executeQuery($sql_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        setAlert('danger', 'รหัสชิ้นส่วนนี้มีอยู่แล้วในระบบ');
        header("Location: company.php?id=$company_id");
        exit;
    }
    
    // อัพเดทข้อมูลชิ้นส่วน
    $sql = "UPDATE parts SET part_no = '$part_no', notes = '$notes' WHERE part_id = '$part_id'";
    
    if (executeQuery($sql)) {
        // อัพโหลดไฟล์
        uploadPartFiles($part_id);
        
        setAlert('success', 'แก้ไขชิ้นส่วนเรียบร้อยแล้ว');
    } else {
        setAlert('danger', 'เกิดข้อผิดพลาดในการแก้ไขชิ้นส่วน');
    }
    
    header("Location: company.php?id=$company_id");
    exit;
}

/**
 * ฟังก์ชันลบชิ้นส่วน
 * @param int $company_id รหัสบริษัท
 */
function deletePart($company_id) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['part_id']) || empty($_POST['part_id'])) {
        setAlert('danger', 'ข้อมูลไม่ครบถ้วน');
        header("Location: company.php?id=$company_id");
        exit;
    }
    
    $part_id = (int)$_POST['part_id'];
    
    // ดึงข้อมูลไฟล์ของชิ้นส่วน
    $sql_files = "SELECT file_id, file_path FROM files WHERE part_id = '$part_id'";
    $result_files = executeQuery($sql_files);
    
    // ลบไฟล์จากระบบไฟล์
    while ($file = mysqli_fetch_assoc($result_files)) {
        $file_path = dirname(__FILE__) . '/' . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // ลบข้อมูลไฟล์จากฐานข้อมูล
    $sql_delete_files = "DELETE FROM files WHERE part_id = '$part_id'";
    executeQuery($sql_delete_files);
    
    // ลบประวัติการเข้าถึงไฟล์
    $sql_delete_logs = "DELETE FROM file_access_logs WHERE file_id IN (SELECT file_id FROM files WHERE part_id = '$part_id')";
    executeQuery($sql_delete_logs);
    
    // ลบข้อมูลชิ้นส่วน
    $sql_delete_part = "DELETE FROM parts WHERE part_id = '$part_id'";
    
    if (executeQuery($sql_delete_part)) {
        setAlert('success', 'ลบชิ้นส่วนเรียบร้อยแล้ว');
    } else {
        setAlert('danger', 'เกิดข้อผิดพลาดในการลบชิ้นส่วน');
    }
    
    header("Location: company.php?id=$company_id");
    exit;
}

/**
 * ฟังก์ชันอัพโหลดไฟล์ของชิ้นส่วน
 * @param int $part_id รหัสชิ้นส่วน
 */
function uploadPartFiles($part_id) {
    // สร้างโฟลเดอร์ตามรหัสชิ้นส่วนถ้ายังไม่มี
    $upload_path = dirname(__FILE__) . '/uploads/' . $part_id . '/';
    if (!file_exists($upload_path)) {
        if (!mkdir($upload_path, 0777, true)) {
            error_log("Failed to create part upload directory in part_process.php: $upload_path");
            return;
        }
        chmod($upload_path, 0777);
    }
    
    // ดึงข้อมูลประเภทไฟล์
    $sql_file_types = "SELECT * FROM file_types";
    $result_file_types = executeQuery($sql_file_types);
    $file_types = [];
    
    while ($row = mysqli_fetch_assoc($result_file_types)) {
        $file_types[$row['type_name']] = $row['file_type_id'];
    }
    
    // อัพโหลดไฟล์ Step bending
    if (isset($_FILES['step_bending']) && $_FILES['step_bending']['error'] == UPLOAD_ERR_OK) {
        uploadFile($_FILES['step_bending'], $part_id, $file_types['Step bending']);
    }
    
    // อัพโหลดไฟล์ Punch V-Die
    if (isset($_FILES['punch_v_die']) && $_FILES['punch_v_die']['error'] == UPLOAD_ERR_OK) {
        uploadFile($_FILES['punch_v_die'], $part_id, $file_types['Punch V-Die']);
    }
    
    // อัพโหลดไฟล์ Drawing
    if (isset($_FILES['drawing']) && $_FILES['drawing']['error'] == UPLOAD_ERR_OK) {
        uploadFile($_FILES['drawing'], $part_id, $file_types['Drawing']);
    }
    
    // อัพโหลดไฟล์ IQS
    if (isset($_FILES['iqs']) && $_FILES['iqs']['error'] == UPLOAD_ERR_OK) {
        uploadFile($_FILES['iqs'], $part_id, $file_types['IQS']);
    }
}
?>
