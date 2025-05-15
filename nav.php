<?php
// ตรวจสอบว่ามีการเริ่มเซสชันแล้วหรือไม่
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="nav-toggle" id="nav-toggle">
    <i class="fas fa-bars" id="menu-icon"></i>
</div>
<nav class="sidebar" id="sidebar">
    <div class="menu-header">
        <span>INFINITY PART</span>
    </div>
    
    <div class="nav-menu">
        <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
              
                <a href="user_management.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'user_management.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Employee</span>
                </a>
                <a href="access_logs.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'access_logs.php') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Access Logs</span>
            </a>
                
                   
            <?php endif; ?>
            
           
            <a href="profile.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            
            <a href="logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </div>
</nav>
