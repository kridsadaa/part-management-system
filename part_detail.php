<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูลและฟังก์ชันจัดการไฟล์
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/file_utils.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// ตรวจสอบว่ามีการส่ง id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$part_id = (int)$_GET['id'];

// ดึงข้อมูลชิ้นส่วน
$sql_part = "SELECT p.*, c.company_id, c.company_name, u.username as created_by_username,
             u.first_name as created_by_first_name, u.last_name as created_by_last_name
             FROM parts p
             JOIN companies c ON p.company_id = c.company_id
             JOIN users u ON p.created_by = u.user_id
             WHERE p.part_id = $part_id";
$result_part = executeQuery($sql_part);

if (!$result_part || mysqli_num_rows($result_part) == 0) {
    header("Location: index.php");
    exit;
}

$part = mysqli_fetch_assoc($result_part);

// ดึงข้อมูลไฟล์ของชิ้นส่วน
$sql_files = "SELECT f.*, ft.type_name, ft.icon, u.username as uploaded_by_username,
              u.first_name as uploaded_by_first_name, u.last_name as uploaded_by_last_name
              FROM files f
              JOIN file_types ft ON f.file_type_id = ft.file_type_id
              JOIN users u ON f.uploaded_by = u.user_id
              WHERE f.part_id = $part_id
              ORDER BY f.upload_date DESC";
$result_files = executeQuery($sql_files);
$files = [];

if ($result_files && mysqli_num_rows($result_files) > 0) {
    while ($row = mysqli_fetch_assoc($result_files)) {
        $files[] = $row;
    }
}

// ดึงข้อมูลประวัติการเข้าถึงไฟล์
$sql_logs = "SELECT l.*, f.file_name, ft.type_name, u.username, u.first_name, u.last_name
             FROM file_access_logs l
             JOIN files f ON l.file_id = f.file_id
             JOIN file_types ft ON f.file_type_id = ft.file_type_id
             JOIN users u ON l.user_id = u.user_id
             WHERE f.part_id = $part_id
             ORDER BY l.access_time DESC
             LIMIT 100";
$result_logs = executeQuery($sql_logs);
$logs = [];

if ($result_logs && mysqli_num_rows($result_logs) > 0) {
    while ($row = mysqli_fetch_assoc($result_logs)) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดชิ้นส่วน <?php echo $part['part_no']; ?> - INFINITY PART CO.,LTD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
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
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">หน้าหลัก</a></li>
                    <li class="breadcrumb-item"><a href="company.php?id=<?php echo $part['company_id']; ?>"><?php echo $part['company_name']; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $part['part_no']; ?></li>
                </ol>
            </nav>

            <div class="row mb-4">
                <div class="col">
                    <h2>รายละเอียดชิ้นส่วน: <?php echo $part['part_no']; ?></h2>
                </div>
                <div class="col-auto">
                    <a href="company.php?id=<?php echo $part['company_id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> ข้อมูลชิ้นส่วน
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>รหัสชิ้นส่วน</th>
                                    <td><?php echo $part['part_no']; ?></td>
                                </tr>
                                <tr>
                                    <th>บริษัท</th>
                                    <td><?php echo $part['company_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>หมายเหตุ</th>
                                    <td><?php echo $part['notes'] ? $part['notes'] : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>ผู้สร้าง</th>
                                    <td>
                                        <?php echo $part['created_by_first_name'] . ' ' . $part['created_by_last_name']; ?>
                                        (<?php echo $part['created_by_username']; ?>)
                                    </td>
                                </tr>
                                <tr>
                                    <th>วันที่สร้าง</th>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($part['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file"></i> ไฟล์เอกสาร
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($files)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> ยังไม่มีไฟล์เอกสารสำหรับชิ้นส่วนนี้
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ประเภท</th>
                                                <th>ชื่อไฟล์</th>
                                                <th>ขนาด</th>
                                                <th>ผู้อัพโหลด</th>
                                                <th>วันที่อัพโหลด</th>
                                                <th>ดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($files as $file): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas <?php echo $file['icon']; ?>"></i>
                                                            <?php echo $file['type_name']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $file['file_name']; ?></td>
                                                    <td><?php echo formatFileSize($file['file_size']); ?></td>
                                                    <td>
                                                        <?php echo $file['uploaded_by_first_name'] . ' ' . $file['uploaded_by_last_name']; ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i:s', strtotime($file['upload_date'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view_file.php?id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="download_file.php?id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-success">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <?php if (isAdmin()): ?>
                                                                <a href="#" class="btn btn-sm btn-danger" onclick="confirmDeleteFile(<?php echo $file['file_id']; ?>, '<?php echo $file['file_name']; ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <?php if (isAdmin()): ?>
                                <div class="mt-3">
                                    <button class="btn btn-success" onclick="openUploadModal()">
                                        <i class="fas fa-upload"></i> อัพโหลดไฟล์
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history"></i> ประวัติการเข้าถึงไฟล์
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> ยังไม่มีประวัติการเข้าถึงไฟล์
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ผู้ใช้งาน</th>
                                                <th>ไฟล์</th>
                                                <th>ประเภท</th>
                                                <th>การเข้าถึง</th>
                                                <th>วันที่เข้าถึง</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td><?php echo $log['first_name'] . ' ' . $log['last_name']; ?></td>
                                                    <td><?php echo $log['file_name']; ?></td>
                                                    <td><?php echo $log['type_name']; ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($log['access_type'] == 'view') {
                                                            echo '<span class="badge bg-primary">ดู</span>';
                                                        } elseif ($log['access_type'] == 'download') {
                                                            echo '<span class="badge bg-success">ดาวน์โหลด</span>';
                                                        } else {
                                                            echo '<span class="badge bg-secondary">' . $log['access_type'] . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['access_time'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isAdmin()): ?>
        <!-- Upload File Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-upload"></i> อัพโหลดไฟล์
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="uploadForm" action="file_upload.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="part_id" value="<?php echo $part_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">ประเภทไฟล์ <span class="text-danger">*</span></label>
                                <select class="form-select" name="file_type_id" required>
                                    <option value="">เลือกประเภทไฟล์</option>
                                    <?php
                                    $sql_types = "SELECT * FROM file_types";
                                    $result_types = executeQuery($sql_types);
                                    while ($type = mysqli_fetch_assoc($result_types)) {
                                        echo '<option value="' . $type['file_type_id'] . '">' . $type['type_name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">ไฟล์ <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="file" required>
                                <div class="form-text">ขนาดไฟล์สูงสุด: 20MB</div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadForm').submit()">อัพโหลด</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete File Confirmation Modal -->
        <div class="modal fade" id="deleteFileModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">ยืนยันการลบไฟล์</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>คุณต้องการลบไฟล์ <strong id="deleteFileName"></strong> ใช่หรือไม่?</p>
                        <p class="text-danger">การกระทำนี้ไม่สามารถเรียกคืนได้</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <form action="file_delete.php" method="POST">
                            <input type="hidden" name="file_id" id="deleteFileId">
                            <input type="hidden" name="part_id" value="<?php echo $part_id; ?>">
                            <button type="submit" class="btn btn-danger">ลบ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    
    <script>
        function openUploadModal() {
            const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            modal.show();
        }

        function confirmDeleteFile(fileId, fileName) {
            document.getElementById('deleteFileId').value = fileId;
            document.getElementById('deleteFileName').textContent = fileName;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteFileModal'));
            modal.show();
        }
    </script>
</body>
</html>

<?php
/**
 * ฟังก์ชันแปลงขนาดไฟล์ให้อ่านง่าย
 * @param int $bytes ขนาดไฟล์เป็นไบต์
 * @return string ขนาดไฟล์ที่อ่านง่าย
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
