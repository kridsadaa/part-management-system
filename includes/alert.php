<?php
/**
 * Alert Component
 * ไฟล์สำหรับแสดงข้อความแจ้งเตือน (Flash Messages)
 */

// ตรวจสอบว่ามีข้อความแจ้งเตือนหรือไม่
if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'];
    $alert_message = $_SESSION['alert_message'];
    
    // แสดงข้อความแจ้งเตือน
    echo '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">';
    
    // เพิ่มไอคอนตามประเภทการแจ้งเตือน
    switch ($alert_type) {
        case 'success':
            echo '<i class="fas fa-check-circle me-2"></i>';
            break;
        case 'danger':
            echo '<i class="fas fa-exclamation-circle me-2"></i>';
            break;
        case 'warning':
            echo '<i class="fas fa-exclamation-triangle me-2"></i>';
            break;
        case 'info':
            echo '<i class="fas fa-info-circle me-2"></i>';
            break;
    }
    
    echo $alert_message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    
    // ลบข้อความแจ้งเตือนหลังจากแสดงแล้ว
    unset($_SESSION['alert_type']);
    unset($_SESSION['alert_message']);
}
?>
