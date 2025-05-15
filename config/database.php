<?php
/**
 * Database Configuration
 * ไฟล์สำหรับการเชื่อมต่อฐานข้อมูล SQL Server
 */

// Database credentials
define('DB_SERVER', 'HP_KRIDSADA\KRIDSADA'); // ชื่อเซิร์ฟเวอร์พร้อม instance
define('DB_USERNAME', null); // Windows Authentication ใช้ null
define('DB_PASSWORD', null); // Windows Authentication ใช้ null
define('DB_NAME', 'infinity_parts_db');

// Attempt to connect to SQL Server database using PDO
try {
    $conn = new PDO(
        "sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_NAME,
        DB_USERNAME, 
        DB_PASSWORD, 
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );
} catch(PDOException $e) {
    die("ERROR: Could not connect to SQL Server. " . $e->getMessage());
}

// Function to get database connection
function getConnection() {
    global $conn;
    return $conn;
}

// Function to close database connection
function closeConnection() {
    global $conn;
    $conn = null; // PDO way to close connection
}

// Function to execute query and return result
function executeQuery($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        die("ERROR: Could not execute query. " . $e->getMessage());
    }
}

// Function to get last inserted ID
function getLastInsertId() {
    global $conn;
    return $conn->lastInsertId();
}

// Function to escape string for database
function escapeString($string) {
    // PDO uses prepared statements, which handle escaping properly
    // This function is kept for backward compatibility
    return $string;
}

/**
 * ฟังก์ชันสำหรับตั้งค่าข้อความแจ้งเตือน
 * @param string $type ประเภทการแจ้งเตือน (success, danger, warning, info)
 * @param string $message ข้อความแจ้งเตือน
 */
function setAlert($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}