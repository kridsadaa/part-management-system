<?php
/**
 * Logout Process
 * ไฟล์สำหรับการออกจากระบบ
 */

// Include authentication functions
require_once 'includes/auth.php';

// Logout user
logout();

// Redirect to login page
header("Location: login.php");
exit;
?>
