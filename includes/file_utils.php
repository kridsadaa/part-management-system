<?php
/**
 * File Utilities
 * ไฟล์สำหรับฟังก์ชันที่เกี่ยวข้องกับการจัดการไฟล์
 */

// Include database configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// กำหนดโฟลเดอร์สำหรับเก็บไฟล์
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');

// ตรวจสอบและสร้างโฟลเดอร์หลักถ้ายังไม่มี
if (!file_exists(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0777, true)) {
        error_log("Failed to create base upload directory: " . UPLOAD_DIR);
    } else {
        chmod(UPLOAD_DIR, 0777);
        error_log("Created base upload directory: " . UPLOAD_DIR);
    }
}

if (!is_writable(UPLOAD_DIR)) {
    error_log("Base upload directory is not writable: " . UPLOAD_DIR);
    // พยายามแก้ไขสิทธิ์
    chmod(UPLOAD_DIR, 0777);
    if (!is_writable(UPLOAD_DIR)) {
        error_log("Failed to set writable permission on base upload directory: " . UPLOAD_DIR);
    }
}

/**
 * ฟังก์ชันอัพโหลดไฟล์
 * @param array $file ข้อมูลไฟล์จาก $_FILES
 * @param int $part_id รหัสชิ้นส่วน
 * @param int $file_type_id รหัสประเภทไฟล์
 * @return array ผลการอัพโหลดไฟล์
 */
function uploadFile($file, $part_id, $file_type_id) {
    // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนอัพโหลดไฟล์'];
    }
    
    // ตรวจสอบว่ามีไฟล์ที่อัพโหลดหรือไม่
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์: ' . getUploadErrorMessage($file['error'])];
    }
    
    // ตรวจสอบขนาดไฟล์ (จำกัดที่ 20MB)
    $max_size = 20 * 1024 * 1024; // 20MB
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'ไฟล์มีขนาดใหญ่เกินไป (จำกัดที่ 20MB)'];
    }
    
    // ตรวจสอบว่าโฟลเดอร์ uploads มีอยู่และมีสิทธิ์ในการเขียน
    $base_upload_dir = dirname(__DIR__) . '/uploads/';
    if (!file_exists($base_upload_dir)) {
        if (!mkdir($base_upload_dir, 0777, true)) {
            error_log("Failed to create base upload directory: $base_upload_dir");
            return ['success' => false, 'message' => 'ไม่สามารถสร้างโฟลเดอร์หลักสำหรับเก็บไฟล์ได้'];
        }
        chmod($base_upload_dir, 0777);
        error_log("Created base upload directory: $base_upload_dir");
    }

    if (!is_writable($base_upload_dir)) {
        error_log("Base upload directory is not writable: $base_upload_dir");
        // พยายามแก้ไขสิทธิ์
        chmod($base_upload_dir, 0777);
        if (!is_writable($base_upload_dir)) {
            return ['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเขียนโฟลเดอร์หลักสำหรับเก็บไฟล์'];
        }
    }
    
    // สร้างโฟลเดอร์ตามรหัสชิ้นส่วนถ้ายังไม่มี
    $upload_path = $base_upload_dir . $part_id . '/';
    if (!file_exists($upload_path)) {
        if (!mkdir($upload_path, 0777, true)) {
            error_log("Failed to create part upload directory: $upload_path");
            return ['success' => false, 'message' => 'ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บไฟล์ได้'];
        }
        chmod($upload_path, 0777);
        error_log("Created part upload directory: $upload_path");
    }
    
    if (!is_writable($upload_path)) {
        error_log("Part upload directory is not writable: $upload_path");
        // พยายามแก้ไขสิทธิ์
        chmod($upload_path, 0777);
        if (!is_writable($upload_path)) {
            return ['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเขียนโฟลเดอร์สำหรับเก็บไฟล์'];
        }
    }
    
    // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำกัน
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_path . $new_filename;
    
    // อัพโหลดไฟล์
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // ตั้งค่าสิทธิ์ของไฟล์
        chmod($file_path, 0644);
        
        // บันทึกข้อมูลไฟล์ลงฐานข้อมูล
        $user_id = $_SESSION['user_id'];
        $original_filename = escapeString($file['name']);
        $file_size = $file['size'];
        $file_extension = escapeString($file_extension);
        $relative_path = 'uploads/' . $part_id . '/' . $new_filename;
        
        $sql = "INSERT INTO files (part_id, file_type_id, file_name, file_path, file_size, file_extension, uploaded_by) 
                VALUES (@part_id, @file_type_id, @original_filename, @relative_path, @file_size, @file_extension, @user_id);";
        
        if (executeQuery($sql)) {
            $file_id = SELECT SCOPE_IDENTITY() AS last_insert_id;;
            return [
                'success' => true, 
                'message' => 'อัพโหลดไฟล์เรียบร้อยแล้ว', 
                'file_id' => $file_id,
                'file_path' => $relative_path
            ];
        } else {
            // ลบไฟล์ที่อัพโหลดแล้วถ้าบันทึกลงฐานข้อมูลไม่สำเร็จ
            unlink($file_path);
            global $conn;
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูลไฟล์: ' . mysqli_error($conn)];
        }
    } else {
        error_log("Failed to move uploaded file from {$file['tmp_name']} to {$file_path}");
        error_log("Upload tmp_name exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No'));
        error_log("Upload path writable: " . (is_writable(dirname($file_path)) ? 'Yes' : 'No'));
        error_log("PHP upload error code: " . $file['error']);
        
        return [
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์: ไม่สามารถย้ายไฟล์ได้', 
            'error_details' => [
                'tmp_name' => $file['tmp_name'],
                'target_path' => $file_path,
                'tmp_exists' => file_exists($file['tmp_name']),
                'target_dir_writable' => is_writable(dirname($file_path)),
                'error_code' => $file['error']
            ]
        ];
    }
}

/**
 * ฟังก์ชันดาวน์โหลดไฟล์
 * @param int $file_id รหัสไฟล์
 * @return array ผลการดาวน์โหลดไฟล์
 */
function downloadFile($file_id) {
    // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนดาวน์โหลดไฟล์'];
    }
    
    // ดึงข้อมูลไฟล์จากฐานข้อมูล
    $sql = "SELECT * FROM files WHERE file_id = @file_id;";
    $result = executeQuery($sql);
    
    if (mysqli_num_rows($result) == 1) {
        $file = mysqli_fetch_assoc($result);
        $file_path = dirname(__DIR__) . '/' . $file['file_path'];
        
        // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
        if (file_exists($file_path)) {
            // บันทึกประวัติการเข้าถึงไฟล์
            logFileAccess($file_id, 'download');
            
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
        } else {
            return ['success' => false, 'message' => 'ไม่พบไฟล์ในระบบ'];
        }
    } else {
        return ['success' => false, 'message' => 'ไม่พบข้อมูลไฟล์ในระบบ'];
    }
}

/**
 * ฟังก์ชันดูไฟล์
 * @param int $file_id รหัสไฟล์
 * @return array ผลการดูไฟล์
 */
function viewFile($file_id) {
    // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนดูไฟล์'];
    }
    
    // ดึงข้อมูลไฟล์จากฐานข้อมูล
    $sql = "SELECT * FROM files WHERE file_id = '$file_id'";
    $result = executeQuery($sql);
    
    if (mysqli_num_rows($result) == 1) {
        $file = mysqli_fetch_assoc($result);
        $file_path = dirname(__DIR__) . '/' . $file['file_path'];
        
        // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
        if (file_exists($file_path)) {
            // บันทึกประวัติการเข้าถึงไฟล์
            logFileAccess($file_id, 'view');
            
            // กำหนด Content-Type ตามประเภทไฟล์
            $content_type = getContentType($file['file_extension']);
            
            // ส่งไฟล์ให้ผู้ใช้ดู
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            return ['success' => false, 'message' => 'ไม่พบไฟล์ในระบบ'];
        }
    } else {
        return ['success' => false, 'message' => 'ไม่พบข้อมูลไฟล์ในระบบ'];
    }
}

/**
 * ฟังก์ชันลบไฟล์
 * @param int $file_id รหัสไฟล์
 * @return array ผลการลบไฟล์
 */
function deleteFile($file_id) {
    // ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
    if (!isAdmin()) {
        return ['success' => false, 'message' => 'คุณไม่มีสิทธิ์ลบไฟล์'];
    }
    
    // ดึงข้อมูลไฟล์จากฐานข้อมูล
    $sql = "SELECT * FROM files WHERE file_id = '$file_id'";
    $result = executeQuery($sql);
    
    if (mysqli_num_rows($result) == 1) {
        $file = mysqli_fetch_assoc($result);
        $file_path = dirname(__DIR__) . '/' . $file['file_path'];
        
        // ลบไฟล์จากระบบไฟล์
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // ลบข้อมูลไฟล์จากฐานข้อมูล
        $sql_delete = "DELETE FROM files WHERE file_id = @file_id;";
        
        if (executeQuery($sql_delete)) {
            return ['success' => true, 'message' => 'ลบไฟล์เรียบร้อยแล้ว'];
        } else {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลไฟล์'];
        }
    } else {
        return ['success' => false, 'message' => 'ไม่พบข้อมูลไฟล์ในระบบ'];
    }
}

/**
 * ฟังก์ชันดึงข้อมูลไฟล์ตามชิ้นส่วน
 * @param int $part_id รหัสชิ้นส่วน
 * @return array ข้อมูลไฟล์
 */
function getFilesByPart($part_id) {
    $sql = "SELECT f.*, ft.type_name, ft.icon, u.username 
            FROM files f
            JOIN file_types ft ON f.file_type_id = ft.file_type_id
            JOIN users u ON f.uploaded_by = u.user_id
            WHERE f.part_id = @part_id
            ORDER BY f.upload_date DESC;";
    
    $result = executeQuery($sql);
    $files = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $files[] = $row;
    }
    
    return $files;
}

/**
 * ฟังก์ชันดึงข้อมูลประวัติการเข้าถึงไฟล์
 * @param int $file_id รหัสไฟล์
 * @return array ข้อมูลประวัติการเข้าถึงไฟล์
 */
function getFileAccessLogs($file_id) {
    $sql = "SELECT l.*, u.username, u.first_name, u.last_name 
FROM file_access_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.file_id = @file_id
ORDER BY l.access_time DESC;";
    
    $result = executeQuery($sql);
    $logs = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * ฟังก์ชันดึงข้อมูลประวัติการเข้าถึงไฟล์ของผู้ใช้
 * @param int $user_id รหัสผู้ใช้
 * @return array ข้อมูลประวัติการเข้าถึงไฟล์
 */
function getUserFileAccessLogs($user_id) {
    $sql = "SELECT l.*, f.file_name, ft.type_name, p.part_no, c.company_name
FROM file_access_logs l
JOIN files f ON l.file_id = f.file_id
JOIN file_types ft ON f.file_type_id = ft.file_type_id
JOIN parts p ON f.part_id = p.part_id
JOIN companies c ON p.company_id = c.company_id
WHERE l.user_id = @user_id
ORDER BY l.access_time DESC;";
    
    $result = executeQuery($sql);
    $logs = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * ฟังก์ชันแปลรหัสข้อผิดพลาดในการอัพโหลดไฟล์
 * @param int $error_code รหัสข้อผิดพลาด
 * @return string ข้อความแสดงข้อผิดพลาด
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'ไฟล์มีขนาดใหญ่เกินกว่าที่กำหนดในไฟล์ php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'ไฟล์มีขนาดใหญ่เกินกว่าที่กำหนดในฟอร์ม HTML';
        case UPLOAD_ERR_PARTIAL:
            return 'ไฟล์ถูกอัพโหลดเพียงบางส่วนเท่านั้น';
        case UPLOAD_ERR_NO_FILE:
            return 'ไม่มีไฟล์ที่อัพโหลด';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'ไม่พบโฟลเดอร์ชั่วคราวสำหรับเก็บไฟล์';
        case UPLOAD_ERR_CANT_WRITE:
            return 'ไม่สามารถเขียนไฟล์ลงดิสก์ได้';
        case UPLOAD_ERR_EXTENSION:
            return 'การอัพโหลดไฟล์ถูกหยุดโดยส่วนขยาย PHP';
        default:
            return 'เกิดข้อผิดพลาดที่ไม่รู้จัก (รหัส: ' . $error_code . ')';
    }
}

/**
 * ฟังก์ชันกำหนด Content-Type ตามประเภทไฟล์
 * @param string $extension นามสกุลไฟล์
 * @return string Content-Type
 */
function getContentType($extension) {
    $extension = strtolower($extension);
    
    switch ($extension) {
        case 'pdf':
            return 'application/pdf';
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'png':
            return 'image/png';
        case 'gif':
            return 'image/gif';
        case 'doc':
            return 'application/msword';
        case 'docx':
            return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        case 'xls':
            return 'application/vnd.ms-excel';
        case 'xlsx':
            return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        case 'ppt':
            return 'application/vnd.ms-powerpoint';
        case 'pptx':
            return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        case 'txt':
            return 'text/plain';
        case 'zip':
            return 'application/zip';
        case 'rar':
            return 'application/x-rar-compressed';
        default:
            return 'application/octet-stream';
    }
}
?>
