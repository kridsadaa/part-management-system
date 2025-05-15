<?php
// เริ่ม session
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่ (ปิดการตรวจสอบชั่วคราวเพื่อการทดสอบ)
/*
if (!isset($_SESSION['user_id'])) {
    // ส่งค่ากลับเป็น JSON แสดงข้อผิดพลาด
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}
*/

// เชื่อมต่อกับฐานข้อมูล
require_once '../config/database.php';

// ฟังก์ชันสำหรับดึงข้อมูลกิจกรรมการเข้าถึงไฟล์ในช่วง 7 วันล่าสุด
function getAccessActivity($conn) {
    // สร้างอาร์เรย์สำหรับเก็บข้อมูล
    $result = [
        'labels' => [],
        'view' => []
    ];
    
    // สร้างอาร์เรย์สำหรับเก็บวันที่ในช่วง 7 วันล่าสุด
    $dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = $date;
        $result['labels'][] = date('d/m/Y', strtotime($date));
        
        // เริ่มต้นค่าเป็น 0 สำหรับการดู
        $result['view'][] = 0;
    }
    
    // สร้าง SQL query สำหรับดึงข้อมูลกิจกรรมการเข้าถึงไฟล์ในช่วง 7 วันล่าสุด
    $sql = "SELECT CONVERT(DATE, l.access_time) as access_date, l.access_type, COUNT(*) as count
            FROM file_access_logs l
            WHERE l.access_time >= DATEADD(DAY, -6, CONVERT(DATE, GETDATE()))
            AND l.access_type = 'view'
            GROUP BY CONVERT(DATE, l.access_time), l.access_type
            ORDER BY access_date";
    
    // ใช้ executeQuery เพื่อรันคำสั่ง SQL
    $data = executeQuery($sql);
    
    // นำข้อมูลที่ได้มาเก็บในอาร์เรย์
    while ($row = sqlsrv_fetch_array($data, SQLSRV_FETCH_ASSOC)) {
        // ในกรณี SQL Server, ค่า date อาจจะเป็น PHP DateTime object
        $date_obj = $row['access_date'];
        
        // แปลง DateTime object เป็น string format 'Y-m-d'
        if ($date_obj instanceof DateTime) {
            $date = $date_obj->format('Y-m-d');
        } else {
            // ถ้าเป็น string อยู่แล้ว ก็แปลงเป็น format 'Y-m-d'
            $date = date('Y-m-d', strtotime($date_obj));
        }
        
        $type = $row['access_type'];
        $count = $row['count'];
        
        // หาตำแหน่งของวันที่ในอาร์เรย์
        $index = array_search($date, $dates);
        
        // ถ้าพบวันที่ในอาร์เรย์ ให้เพิ่มค่าตามประเภทการเข้าถึง
        if ($index !== false) {
            switch ($type) {
                case 'view':
                    $result['view'][$index] = (int)$count;
                    break;
            }
        }
    }
    
    return $result;
}

// ดึงข้อมูลกิจกรรมการเข้าถึงไฟล์
$activity_data = getAccessActivity($conn);

// ส่งค่ากลับเป็น JSON
header('Content-Type: application/json');
echo json_encode($activity_data);

// ปิดการเชื่อมต่อกับฐานข้อมูล
closeConnection();
?>