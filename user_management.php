<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูล
require_once 'config/database.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์แอดมิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// กำหนดจำนวนรายการต่อหน้า
$records_per_page = 10;

// รับค่าหน้าปัจจุบัน
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// รับค่าคำค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';

// คำนวณ offset สำหรับ SQL LIMIT
$offset = ($current_page - 1) * $records_per_page;

// ดึงข้อมูลผู้ใช้ทั้งหมดจากฐานข้อมูลพร้อมการแบ่งหน้า
$sql = "SELECT * FROM users WHERE username LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' ORDER BY user_id LIMIT $offset, $records_per_page";
$result = executeQuery($sql);
$users = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// นับจำนวนผู้ใช้ทั้งหมดเพื่อคำนวณจำนวนหน้า
$count_sql = "SELECT COUNT(*) as total FROM users WHERE username LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%'";
$count_result = executeQuery($count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - INFINITY PART CO.,LTD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="custom.css">
    <style>
        .page-title {
            color: #ff0000;
        }
        .search-input {
            border: 1px solid #ff0000;
        }
        .btn-search {
            background-color: #ff0000;
            color: #fff;
            border: none;
        }
        .btn-add-user {
            background-color: #ff0000;
            color: #fff;
            border: none;
        }
        .user-management-card {
            border: 1px solid #ff0000;
        }
        .user-table {
            border: 1px solid #ff0000;
        }
        .user-row {
            transition: all 0.3s ease;
        }
        .user-row:hover {
            transform: translateX(5px);
        }
        .btn-edit {
            background-color: #ff0000;
            color: #fff;
            border: none;
        }
        .btn-delete {
            background-color: #ff0000;
            color: #fff;
            border: none;
        }
        .btn-save {
            background-color: #ff0000;
            color: #fff;
            border: none;
        }
    </style>
</head>
<body>
    <div class="menu-overlay"></div>
    <?php include 'nav.php'; ?>

    <header>
        <div class="header-left"></div>
        <div class="header-right">
            <div class="company-name">INFINITY PART CO.,LTD</div>
            <div class="company-name-th">บริษัท อินฟินิตี้ พาร์ท จำกัด</div>
        </div>
    </header>

    <main>
        <div class="container mt-4">
            <div class="row mb-4">
                <div class="col">
                    <h2 class="page-title"><i class="fas fa-users me-2"></i>Employee Management</h2>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="input-group" style="max-width: 500px;">
                            <input type="text" id="searchInput" class="form-control search-input" placeholder="ค้นหาพนักงาน..." value="<?php echo $search; ?>">
                            <button class="btn btn-search" onclick="searchUsers()">
                                <i class="fas fa-search"></i> ค้นหา
                            </button>
                            <?php if (!empty($search)): ?>
                            <button class="btn btn-secondary" onclick="clearSearch()">
                                <i class="fas fa-times"></i> ล้าง
                            </button>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-add-user" onclick="openAddModal()">
                            <i class="fas fa-user-plus"></i> เพิ่มพนักงาน
                        </button>
                    </div>
                </div>
            </div>

            <div class="card user-management-card">
                <div class="card-body">
                    <?php if (!empty($search)): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i> ผลการค้นหาสำหรับ: <strong>"<?php echo $search; ?>"</strong>
                        <a href="user_management.php?page=1" class="float-end text-decoration-none">
                            <i class="fas fa-times"></i> ยกเลิกการค้นหา
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover user-table">
                            <thead>
                                <tr>
                                  
                                    <th>รหัสพนักงาน</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>แผนก</th>
                                    <th>ตำแหน่ง</th>
                                    <th>อีเมล</th>
                                    <th>บทบาท</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">ไม่พบข้อมูลผู้ใช้</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="user-row">
                                           
                                            <td><?php echo $user['employee_id'] ?? '-'; ?></td>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                            <td><?php echo $user['department'] ?? '-'; ?></td>
                                            <td><?php echo $user['position'] ?? '-'; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="badge <?php echo ($user['role'] == 'admin') ? 'bg-danger' : (($user['role'] == 'staff') ? 'bg-primary' : 'bg-secondary'); ?>">
                                                    <?php echo $user['role']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo ($user['status'] == 'active') ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $user['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-edit" onclick="openEditModal(<?php echo $user['user_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete" onclick="openDeleteModal(<?php echo $user['user_id']; ?>)">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination-container mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&search=<?php echo $search; ?>" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&search=<?php echo $search; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        // แสดงหมายเลขหน้า
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&search=<?php echo $search; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo $search; ?>" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="text-center mt-2">
                    <small class="text-muted">แสดง <?php echo count($users); ?> รายการ จากทั้งหมด <?php echo $total_records; ?> รายการ (หน้า <?php echo $current_page; ?> จาก <?php echo $total_pages; ?>)</small>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" id="userModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">เพิ่มพนักงานใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm" action="user_process.php" method="POST">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="user_id" id="userId" value="">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id">
                                </div>
                                <div class="col-md-6">
                                    <label for="username" class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="department" class="form-label">แผนก</label>
                                    <input type="text" class="form-control" id="department" name="department">
                                </div>
                                <div class="col-md-6">
                                    <label for="position" class="form-label">ตำแหน่ง</label>
                                    <input type="text" class="form-control" id="position" name="position">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="role" class="form-label">บทบาท <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="admin">Admin</option>
                                        <option value="staff">Staff</option>
                                        <option value="viewer">Viewer</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">รหัสผ่าน <span class="text-danger password-required">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted password-hint">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">สถานะ <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-save">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">ยืนยันการลบ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <form action="user_process.php" method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" id="deleteUserId" value="">
                            <button type="submit" class="btn btn-danger">ลบ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to search users
        function searchUsers() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            
            // ถ้ามีการค้นหา ให้ redirect ไปยังหน้าแรกพร้อมกับพารามิเตอร์การค้นหา
            if (searchInput.trim() !== '') {
                window.location.href = 'user_management.php?page=1&search=' + encodeURIComponent(searchInput);
            } else {
                window.location.href = 'user_management.php?page=1';
            }
        }
        
        // Function to clear search
        function clearSearch() {
            window.location.href = 'user_management.php?page=1';
        }
        
        // Function to open add modal
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มพนักงานใหม่';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();
            
            // Show password required indicator
            document.querySelector('.password-required').style.display = '';
            document.querySelector('.password-hint').style.display = 'none';
            document.getElementById('password').setAttribute('required', 'required');
            
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
        
        // Function to open edit modal
        function openEditModal(userId) {
            document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลพนักงาน';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = userId;
            
            // Hide password required indicator
            document.querySelector('.password-required').style.display = 'none';
            document.querySelector('.password-hint').style.display = '';
            document.getElementById('password').removeAttribute('required');
            
            // Fetch user data
            fetch(`get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('employee_id').value = user.employee_id || '';
                        document.getElementById('username').value = user.username;
                        document.getElementById('first_name').value = user.first_name;
                        document.getElementById('last_name').value = user.last_name;
                        document.getElementById('department').value = user.department || '';
                        document.getElementById('position').value = user.position || '';
                        document.getElementById('email').value = user.email;
                        document.getElementById('role').value = user.role;
                        document.getElementById('status').value = user.status;
                        document.getElementById('password').value = '';
                        
                        const modal = new bootstrap.Modal(document.getElementById('userModal'));
                        modal.show();
                    } else {
                        alert('ไม่สามารถดึงข้อมูลผู้ใช้ได้');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
                });
        }
        
        // Function to open delete modal
        function openDeleteModal(userId) {
            document.getElementById('deleteUserId').value = userId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        // Add event listener for search input
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchUsers();
            }
        });

        // Add hover effects for user rows
        document.querySelectorAll('.user-row').forEach(row => {
            row.addEventListener('mouseover', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'all 0.3s ease';
            });
            
            row.addEventListener('mouseout', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>
