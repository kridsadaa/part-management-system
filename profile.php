<?php
/**
 * Profile Page
 * หน้าแสดงและแก้ไขข้อมูลโปรไฟล์ของผู้ใช้
 */

// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูลและฟังก์ชันการตรวจสอบสิทธิ์
require_once 'config/database.php';
require_once 'includes/auth.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// กำหนดชื่อหน้า
$pageTitle = 'โปรไฟล์ของฉัน';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = executeQuery($sql);
$user = mysqli_fetch_assoc($result);

// ตรวจสอบว่ามีการส่งฟอร์มแก้ไขข้อมูลส่วนตัว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        // รับค่าจากฟอร์ม
        $first_name = escapeString($_POST['first_name']);
        $last_name = escapeString($_POST['last_name']);
        $email = escapeString($_POST['email']);
        $department = isset($_POST['department']) ? escapeString($_POST['department']) : null;
        $position = isset($_POST['position']) ? escapeString($_POST['position']) : null;
        
        // ตรวจสอบว่า email ซ้ำหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
        $check_email_sql = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id";
        $check_email_result = executeQuery($check_email_sql);
        
        if (mysqli_num_rows($check_email_result) > 0) {
            setAlert('danger', 'อีเมลนี้มีอยู่ในระบบแล้ว กรุณาใช้อีเมลอื่น');
        } else {
            // อัปเดตข้อมูลผู้ใช้
            $update_sql = "UPDATE users SET 
                        first_name = '$first_name',
                        last_name = '$last_name',
                        email = '$email',
                        department = '$department',
                        position = '$position',
                        updated_at = NOW()
                        WHERE user_id = $user_id";
            
            if (executeQuery($update_sql)) {
                // อัปเดตข้อมูลในเซสชัน
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                
                setAlert('success', 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว');
                
                // ดึงข้อมูลผู้ใช้ใหม่
                $result = executeQuery($sql);
                $user = mysqli_fetch_assoc($result);
            } else {
                setAlert('danger', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . mysqli_error(getConnection()));
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        // รับค่าจากฟอร์ม
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // ตรวจสอบว่ารหัสผ่านใหม่และยืนยันรหัสผ่านตรงกัน
        if ($new_password !== $confirm_password) {
            setAlert('danger', 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
        } else {
            // เรียกใช้ฟังก์ชันเปลี่ยนรหัสผ่าน
            $result = changePassword($user_id, $current_password, $new_password);
            
            if ($result['success']) {
                setAlert('success', $result['message']);
            } else {
                setAlert('danger', $result['message']);
            }
        }
    }
}

// รวมไฟล์ header
include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background-color: #ff0000; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i> โปรไฟล์ของฉัน
                    </h5>
                </div>
                <div class="card-body">
                    <?php include 'includes/alert.php'; ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card profile-card">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-user-circle fa-5x" style="color: #ff0000;"></i>
                                    </div>
                                    <h5 class="card-title"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h5>
                                    <p class="card-text text-muted"><?php echo $user['username']; ?></p>
                                    <p class="card-text">
                                        <span class="badge" style="background-color: <?php echo ($user['role'] == 'admin') ? '#ff0000' : '#28a745'; ?>;">
                                            <?php echo ($user['role'] == 'admin') ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งานทั่วไป'; ?>
                                        </span>
                                    </p>
                                    <div class="mt-3">
                                        <p class="mb-1"><i class="fas fa-envelope me-2" style="color: #ff6b6b;"></i> <?php echo $user['email']; ?></p>
                                        <?php if (!empty($user['department'])): ?>
                                        <p class="mb-1"><i class="fas fa-building me-2" style="color: #ff6b6b;"></i> <?php echo $user['department']; ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($user['position'])): ?>
                                        <p class="mb-1"><i class="fas fa-briefcase me-2" style="color: #ff6b6b;"></i> <?php echo $user['position']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile" type="button" role="tab" aria-controls="edit-profile" aria-selected="true">แก้ไขข้อมูลส่วนตัว</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="change-password-tab" data-bs-toggle="tab" data-bs-target="#change-password" type="button" role="tab" aria-controls="change-password" aria-selected="false">เปลี่ยนรหัสผ่าน</button>
                                </li>
                                <?php if (isAdmin()): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">กิจกรรมล่าสุด</button>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="profileTabsContent">
                                <!-- แก้ไขข้อมูลส่วนตัว -->
                                <div class="tab-pane fade show active" id="edit-profile" role="tabpanel" aria-labelledby="edit-profile-tab">
                                    <form method="post" action="profile.php">
                                        <input type="hidden" name="action" value="update_profile">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="department" class="form-label">แผนก</label>
                                                <input type="text" class="form-control" id="department" name="department" value="<?php echo $user['department']; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="position" class="form-label">ตำแหน่ง</label>
                                                <input type="text" class="form-control" id="position" name="position" value="<?php echo $user['position']; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">ชื่อผู้ใช้</label>
                                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                                            <div class="form-text">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</div>
                                        </div>
                                        
                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-profile">
                                                <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- เปลี่ยนรหัสผ่าน -->
                                <div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                                    <form method="post" action="profile.php">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-profile">
                                                <i class="fas fa-key me-2"></i> เปลี่ยนรหัสผ่าน
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- กิจกรรมล่าสุด -->
                                <?php if (isAdmin()): ?>
                                <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                                    <h5 class="mb-3" style="color: #ff0000;"><i class="fas fa-history me-2"></i> ประวัติการเข้าถึงไฟล์ล่าสุด</h5>
                                    
                                    <?php
                                    // ดึงประวัติการเข้าถึงไฟล์ล่าสุด 10 รายการ
                                    $logs_sql = "SELECT l.*, f.file_name, f.file_path, f.file_size, ft.type_name as file_type_name, 
                                               c.company_name, u.first_name, u.last_name
                                               FROM access_logs l
                                               JOIN files f ON l.file_id = f.file_id
                                               JOIN file_types ft ON f.file_type_id = ft.file_type_id
                                               JOIN companies c ON f.company_id = c.company_id
                                               JOIN users u ON l.user_id = u.user_id
                                               WHERE l.user_id = $user_id
                                               ORDER BY l.accessed_at DESC
                                               LIMIT 10";
                                    
                                    $logs_result = executeQuery($logs_sql);
                                    
                                    if ($logs_result && mysqli_num_rows($logs_result) > 0) {
                                        echo '<div class="table-responsive">';
                                        echo '<table class="table table-hover">';
                                        echo '<thead style="background-color: #fff0f0;">';
                                        echo '<tr>';
                                        echo '<th>ไฟล์</th>';
                                        echo '<th>ประเภท</th>';
                                        echo '<th>บริษัท</th>';
                                        echo '<th>เวลาที่เข้าถึง</th>';
                                        echo '</tr>';
                                        echo '</thead>';
                                        echo '<tbody>';
                                        
                                        while ($log = mysqli_fetch_assoc($logs_result)) {
                                            echo '<tr class="activity-row">';
                                            echo '<td><i class="fas fa-file me-2" style="color: #ff6b6b;"></i>' . $log['file_name'] . '</td>';
                                            echo '<td><span class="badge rounded-pill" style="background-color: #ff6b6b;">' . $log['file_type_name'] . '</span></td>';
                                            echo '<td>' . $log['company_name'] . '</td>';
                                            echo '<td>' . date('d/m/Y H:i', strtotime($log['accessed_at'])) . '</td>';
                                            echo '</tr>';
                                        }
                                        
                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="alert alert-info">';
                                        echo '<i class="fas fa-info-circle me-2"></i> ไม่พบประวัติการเข้าถึงไฟล์';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// แสดง Toast เมื่อมีการบันทึกข้อมูล
document.addEventListener('DOMContentLoaded', function() {
    // เมื่อมีการส่งฟอร์มแก้ไขข้อมูลส่วนตัว
    document.querySelector('form[action="profile.php"]').addEventListener('submit', function() {
        // แสดง Toast ว่ากำลังบันทึกข้อมูล
        showToast('กำลังบันทึกข้อมูล...', 'info');
    });
    
    // เมื่อมีการส่งฟอร์มเปลี่ยนรหัสผ่าน
    document.querySelectorAll('form[action="profile.php"]')[1].addEventListener('submit', function() {
        // แสดง Toast ว่ากำลังบันทึกข้อมูล
        showToast('กำลังดำเนินการ...', 'info');
    });
    
    // ฟังก์ชันสำหรับแสดง Toast
    function showToast(message, type = 'info') {
        // สร้าง Toast element
        const toastContainer = document.createElement('div');
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '5';
        
        const toastElement = document.createElement('div');
        toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        const toastBody = document.createElement('div');
        toastBody.className = 'd-flex';
        
        const toastBodyText = document.createElement('div');
        toastBodyText.className = 'toast-body';
        toastBodyText.innerHTML = message;
        
        const toastCloseButton = document.createElement('button');
        toastCloseButton.type = 'button';
        toastCloseButton.className = 'btn-close btn-close-white me-2 m-auto';
        toastCloseButton.setAttribute('data-bs-dismiss', 'toast');
        toastCloseButton.setAttribute('aria-label', 'Close');
        
        toastBody.appendChild(toastBodyText);
        toastBody.appendChild(toastCloseButton);
        toastElement.appendChild(toastBody);
        toastContainer.appendChild(toastElement);
        
        // เพิ่ม Toast ลงใน DOM
        document.body.appendChild(toastContainer);
        
        // สร้าง Bootstrap Toast object
        const toast = new bootstrap.Toast(toastElement, {
            delay: 3000
        });
        
        // แสดง Toast
        toast.show();
        
        // ลบ Toast หลังจากซ่อน
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastContainer.remove();
        });
    }
});

// เมื่อเอกสารโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // จัดการการเปลี่ยนแท็บ
    const tabLinks = document.querySelectorAll('.nav-link');
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('click', function() {
            // เอาคลาส active ออกจากทุกแท็บ
            tabLinks.forEach(function(link) {
                link.classList.remove('active');
            });
            // เพิ่มคลาส active ให้กับแท็บที่คลิก
            this.classList.add('active');
        });
    });
    
    // เพิ่มเอฟเฟกต์ hover ให้กับการ์ดโปรไฟล์
    const profileCard = document.querySelector('.profile-card');
    if (profileCard) {
        profileCard.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
        });
        
        profileCard.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    }
    
    // เพิ่มเอฟเฟกต์ให้กับปุ่ม
    const buttons = document.querySelectorAll('.btn-profile');
    buttons.forEach(function(button) {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
});
</script>
