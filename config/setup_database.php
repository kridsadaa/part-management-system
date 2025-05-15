<?php
/**
 * Database Setup Script
 * ไฟล์สำหรับสร้างโครงสร้างฐานข้อมูลทั้งหมด
 */

// Include database configuration
require_once 'database.php';

// Array to store results
$results = array();

// Create users table
$sql_users = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
CREATE TABLE users (
    user_id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) NOT NULL UNIQUE,
    password NVARCHAR(255) NOT NULL,
    first_name NVARCHAR(100) NOT NULL,
    last_name NVARCHAR(100) NOT NULL,
    email NVARCHAR(100) NOT NULL UNIQUE,
    role NVARCHAR(10) NOT NULL DEFAULT 'viewer' CHECK (role IN ('admin', 'staff', 'viewer')),
    status NVARCHAR(10) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
)";

if (executeQuery($sql_users)) {
    $results[] = "ตาราง users สร้างเรียบร้อยแล้ว";
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง users: " . sqlsrv_errors()[0]['message'];
}

// Create companies table
$sql_companies = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='companies' AND xtype='U')
CREATE TABLE companies (
    company_id INT IDENTITY(1,1) PRIMARY KEY,
    company_name NVARCHAR(100) NOT NULL,
    company_name_th NVARCHAR(100),
    contact_person NVARCHAR(100),
    phone NVARCHAR(20),
    email NVARCHAR(100),
    address NVARCHAR(MAX),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
)";

if (executeQuery($sql_companies)) {
    $results[] = "ตาราง companies สร้างเรียบร้อยแล้ว";
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง companies: " . sqlsrv_errors()[0]['message'];
}

// Create parts table
$sql_parts = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='parts' AND xtype='U')
CREATE TABLE parts (
    part_id INT IDENTITY(1,1) PRIMARY KEY,
    company_id INT NOT NULL,
    part_no NVARCHAR(50) NOT NULL,
    notes NVARCHAR(MAX),
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_parts_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT FK_parts_user FOREIGN KEY (created_by) REFERENCES users(user_id),
    CONSTRAINT UQ_company_part UNIQUE (company_id, part_no)
)";

if (executeQuery($sql_parts)) {
    $results[] = "ตาราง parts สร้างเรียบร้อยแล้ว";
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง parts: " . sqlsrv_errors()[0]['message'];
}

// Create file_types table
$sql_file_types = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='file_types' AND xtype='U')
CREATE TABLE file_types (
    file_type_id INT IDENTITY(1,1) PRIMARY KEY,
    type_name NVARCHAR(50) NOT NULL UNIQUE,
    description NVARCHAR(MAX),
    icon NVARCHAR(50)
)";

if (executeQuery($sql_file_types)) {
    $results[] = "ตาราง file_types สร้างเรียบร้อยแล้ว";
    
    // Insert default file types - check if they exist first
    $sql_check_types = "IF NOT EXISTS (SELECT * FROM file_types WHERE type_name = 'Step bending')
    BEGIN
        INSERT INTO file_types (type_name, description, icon) VALUES
        ('Step bending', 'ไฟล์ Step bending', 'fa-file-pdf'),
        ('Punch V-Die', 'ไฟล์ Punch V-Die', 'fa-tools'),
        ('Drawing', 'ไฟล์ Drawing', 'fa-drafting-compass'),
        ('IQS', 'ไฟล์ IQS', 'fa-file-alt')
    END";
    
    if (executeQuery($sql_check_types)) {
        $results[] = "เพิ่มข้อมูลประเภทไฟล์เริ่มต้นเรียบร้อยแล้ว";
    } else {
        $results[] = "เกิดข้อผิดพลาดในการเพิ่มข้อมูลประเภทไฟล์: " . sqlsrv_errors()[0]['message'];
    }
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง file_types: " . sqlsrv_errors()[0]['message'];
}

// Create files table
$sql_files = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='files' AND xtype='U')
CREATE TABLE files (
    file_id INT IDENTITY(1,1) PRIMARY KEY,
    part_id INT NOT NULL,
    file_type_id INT NOT NULL,
    file_name NVARCHAR(255) NOT NULL,
    file_path NVARCHAR(255) NOT NULL,
    file_size INT,
    file_extension NVARCHAR(10),
    uploaded_by INT NOT NULL,
    upload_date DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_files_part FOREIGN KEY (part_id) REFERENCES parts(part_id) ON DELETE CASCADE,
    CONSTRAINT FK_files_type FOREIGN KEY (file_type_id) REFERENCES file_types(file_type_id),
    CONSTRAINT FK_files_user FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
)";

if (executeQuery($sql_files)) {
    $results[] = "ตาราง files สร้างเรียบร้อยแล้ว";
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง files: " . sqlsrv_errors()[0]['message'];
}

// Create file_access_logs table
$sql_file_access_logs = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='file_access_logs' AND xtype='U')
CREATE TABLE file_access_logs (
    log_id INT IDENTITY(1,1) PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    access_time DATETIME DEFAULT GETDATE(),
    access_type NVARCHAR(10) NOT NULL CHECK (access_type IN ('view', 'download', 'print')),
    ip_address NVARCHAR(45),
    user_agent NVARCHAR(MAX),
    CONSTRAINT FK_logs_file FOREIGN KEY (file_id) REFERENCES files(file_id) ON DELETE CASCADE,
    CONSTRAINT FK_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (executeQuery($sql_file_access_logs)) {
    $results[] = "ตาราง file_access_logs สร้างเรียบร้อยแล้ว";
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง file_access_logs: " . sqlsrv_errors()[0]['message'];
}

// Create user_sessions table
$sql_user_sessions = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='user_sessions' AND xtype='U')
CREATE TABLE user_sessions (
    session_id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT GETDATE(),
    logout_time DATETIME NULL,
    ip_address NVARCHAR(45),
    user_agent NVARCHAR(MAX),
    status NVARCHAR(10) DEFAULT 'active' CHECK (status IN ('active', 'expired', 'logged_out')),
    CONSTRAINT FK_sessions_user FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (executeQuery($sql_user_sessions)) {
    $results[] = "ตาราง user_sessions สร้างเรียบร้อยแล้ว";
} else {
    $results[] = "เกิดข้อผิดพลาดในการสร้างตาราง user_sessions: " . sqlsrv_errors()[0]['message'];
}

// Create default admin user if not exists
$admin_username = 'admin';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT); // Default password: admin123

$check_admin = "SELECT user_id FROM users WHERE username = '$admin_username'";
$admin_result = executeQuery($check_admin);

if (sqlsrv_has_rows($admin_result) === false) {
    $sql_admin = "INSERT INTO users (username, password, first_name, last_name, email, role) 
                  VALUES ('$admin_username', '$admin_password', 'Admin', 'User', 'admin@example.com', 'admin')";
    
    if (executeQuery($sql_admin)) {
        $results[] = "สร้างผู้ใช้ admin เริ่มต้นเรียบร้อยแล้ว (username: admin, password: admin123)";
    } else {
        $results[] = "เกิดข้อผิดพลาดในการสร้างผู้ใช้ admin: " . sqlsrv_errors()[0]['message'];
    }
} else {
    $results[] = "ผู้ใช้ admin มีอยู่แล้ว";
}

// Create default company if not exists
$check_company = "SELECT company_id FROM companies WHERE company_name = 'MITSUBISHI'";
$company_result = executeQuery($check_company);

if (sqlsrv_has_rows($company_result) === false) {
    $sql_company = "INSERT INTO companies (company_name, company_name_th) 
                   VALUES ('MITSUBISHI', 'มิตซูบิชิ')";
    
    if (executeQuery($sql_company)) {
        $results[] = "สร้างบริษัท MITSUBISHI เริ่มต้นเรียบร้อยแล้ว";
    } else {
        $results[] = "เกิดข้อผิดพลาดในการสร้างบริษัทเริ่มต้น: " . sqlsrv_errors()[0]['message'];
    }
} else {
    $results[] = "บริษัท MITSUBISHI มีอยู่แล้ว";
}

// Output results
echo "<h2>ผลการสร้างฐานข้อมูล</h2>";
echo "<ul>";
foreach ($results as $result) {
    echo "<li>$result</li>";
}
echo "</ul>";

// Close connection
closeConnection();
?>