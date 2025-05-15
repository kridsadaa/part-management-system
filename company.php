<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูล
require_once 'config/database.php';

// ตรวจสอบว่ามีการส่ง company_id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$company_id = (int)$_GET['id'];

// ดึงข้อมูลบริษัท
$sql_company = "SELECT * FROM companies WHERE company_id = $company_id";
$result_company = executeQuery($sql_company);

if (!$result_company || !sqlsrv_has_rows($result_company)) {
    header("Location: index.php");
    exit;
}

$company = sqlsrv_fetch_array($result_company, SQLSRV_FETCH_ASSOC);

// ดึงข้อมูลชิ้นส่วนของบริษัท
$sql_parts = "SELECT p.*, u.username as created_by_username 
              FROM parts p 
              JOIN users u ON p.created_by = u.user_id 
              WHERE p.company_id = $company_id 
              ORDER BY p.part_no";
$result_parts = executeQuery($sql_parts);
$parts = [];

if ($result_parts && sqlsrv_has_rows($result_parts)) {
    while ($row = sqlsrv_fetch_array($result_parts, SQLSRV_FETCH_ASSOC)) {
        $part_id = $row['part_id'];
        
        // ดึงข้อมูลไฟล์ของแต่ละชิ้นส่วน
        $sql_files = "SELECT f.*, ft.type_name, ft.icon 
                      FROM files f 
                      JOIN file_types ft ON f.file_type_id = ft.file_type_id 
                      WHERE f.part_id = $part_id";
        $result_files = executeQuery($sql_files);
        
        $files = [];
        if ($result_files && sqlsrv_has_rows($result_files)) {
            while ($file_row = sqlsrv_fetch_array($result_files, SQLSRV_FETCH_ASSOC)) {
                // จัดการกับค่าวันที่จาก MSSQL
                if (isset($file_row['upload_date']) && $file_row['upload_date'] instanceof DateTime) {
                    $file_row['upload_date_formatted'] = $file_row['upload_date']->format('d/m/Y H:i:s');
                } else if (isset($file_row['upload_date'])) {
                    $file_row['upload_date_formatted'] = date('d/m/Y H:i:s', strtotime($file_row['upload_date']));
                }
                
                // จัดการกับค่าวันที่ updated_at
                if (isset($file_row['updated_at']) && $file_row['updated_at'] instanceof DateTime) {
                    $file_row['updated_at_formatted'] = $file_row['updated_at']->format('d/m/Y H:i:s');
                } else if (isset($file_row['updated_at'])) {
                    $file_row['updated_at_formatted'] = date('d/m/Y H:i:s', strtotime($file_row['updated_at']));
                }
                
                $files[] = $file_row;
            }
        }
        
        // จัดการกับค่าวันที่ created_at และ updated_at ของชิ้นส่วน
        if (isset($row['created_at']) && $row['created_at'] instanceof DateTime) {
            $row['created_at_formatted'] = $row['created_at']->format('d/m/Y H:i:s');
        } else if (isset($row['created_at'])) {
            $row['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($row['created_at']));
        }
        
        if (isset($row['updated_at']) && $row['updated_at'] instanceof DateTime) {
            $row['updated_at_formatted'] = $row['updated_at']->format('d/m/Y H:i:s');
        } else if (isset($row['updated_at'])) {
            $row['updated_at_formatted'] = date('d/m/Y H:i:s', strtotime($row['updated_at']));
        }
        
        // เพิ่มข้อมูลไฟล์เข้าไปในข้อมูลชิ้นส่วน
        $row['files'] = $files;
        $parts[] = $row;
    }
}

// จัดการกับค่าวันที่ของบริษัท
if (isset($company['created_at']) && $company['created_at'] instanceof DateTime) {
    $company['created_at_formatted'] = $company['created_at']->format('d/m/Y H:i:s');
} else if (isset($company['created_at'])) {
    $company['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($company['created_at']));
}

if (isset($company['updated_at']) && $company['updated_at'] instanceof DateTime) {
    $company['updated_at_formatted'] = $company['updated_at']->format('d/m/Y H:i:s');
} else if (isset($company['updated_at'])) {
    $company['updated_at_formatted'] = date('d/m/Y H:i:s', strtotime($company['updated_at']));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $company['company_name']; ?> - INFINITY PART CO.,LTD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Custom CSS for dropup menu */
        .dropup-menu {
            top: auto;
            bottom: 100%;
            margin-bottom: 0.125rem;
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
                    <h2><?php echo $company['company_name']; ?></h2>
                    <?php if (!empty($company['company_name_th'])): ?>
                        <p class="text-muted"><?php echo $company['company_name_th']; ?></p>
                    <?php endif; ?>
                    
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชิ้นส่วน...">
                        <button class="btn btn-primary" onclick="searchParts()">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['role'] == 'admin'): ?>
                        <button class="btn btn-success ms-2" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> เพิ่มชิ้นส่วน
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Part No.</th>
                            <th>Step bending</th>
                            <th>Punch V-Die</th>
                            <th>Drawing</th>
                            <th>IQS</th>
                          
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="partsTableBody">
                        <?php if (empty($parts)): ?>
                            <tr>
                                <td colspan="8" class="text-center">ไม่พบข้อมูลชิ้นส่วน</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach ($parts as $part): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo $part['part_no']; ?></td>
                                    
                                    <?php 
                                    // สร้างตัวแปรเพื่อเก็บข้อมูลไฟล์แต่ละประเภท
                                    $step_bending = null;
                                    $punch_v_die = null;
                                    $drawing = null;
                                    $iqs = null;
                                    
                                    // ตรวจสอบไฟล์แต่ละประเภท
                                    foreach ($part['files'] as $file) {
                                        if ($file['type_name'] == 'Step bending') {
                                            $step_bending = $file;
                                        } elseif ($file['type_name'] == 'Punch V-Die') {
                                            $punch_v_die = $file;
                                        } elseif ($file['type_name'] == 'Drawing') {
                                            $drawing = $file;
                                        } elseif ($file['type_name'] == 'IQS') {
                                            $iqs = $file;
                                        }
                                    }
                                    ?>
                                    
                                    <td>
                                        <?php if ($step_bending): ?>
                                            <a href="view_file.php?id=<?php echo $step_bending['file_id']; ?>" class="btn btn-sm btn-danger" target="_blank">
                                                <i class="fas <?php echo $step_bending['icon']; ?>"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-danger disabled">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($punch_v_die): ?>
                                            <a href="view_file.php?id=<?php echo $punch_v_die['file_id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                <i class="fas <?php echo $punch_v_die['icon']; ?>"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-secondary disabled">
                                                <i class="fas fa-tools"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($drawing): ?>
                                            <a href="view_file.php?id=<?php echo $drawing['file_id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                                <i class="fas <?php echo $drawing['icon']; ?>"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-info disabled">
                                                <i class="fas fa-drafting-compass"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($iqs): ?>
                                            <a href="view_file.php?id=<?php echo $iqs['file_id']; ?>" class="btn btn-sm btn-dark" target="_blank">
                                                <i class="fas <?php echo $iqs['icon']; ?>"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-dark disabled">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                             
                                    
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                จัดการ
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if (isset($_SESSION['logged_in']) && $_SESSION['role'] == 'admin'): ?>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="openEditModal(<?php echo $part['part_id']; ?>, '<?php echo $part['part_no']; ?>', '<?php echo addslashes($part['notes']); ?>')">
                                                        <i class="fas fa-edit"></i> แก้ไข
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="confirmDelete(<?php echo $part['part_id']; ?>, '<?php echo $part['part_no']; ?>')">
                                                        <i class="fas fa-trash"></i> ลบ
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <li>
                                                    <a class="dropdown-item" href="part_detail.php?id=<?php echo $part['part_id']; ?>">
                                                        <i class="fas fa-info-circle"></i> รายละเอียด
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (isset($_SESSION['logged_in']) && $_SESSION['role'] == 'admin'): ?>
        <!-- Add/Edit Modal -->
        <div class="modal fade" id="partModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
                    <div class="modal-header text-white" style="background-color: #e60012;">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle me-2"></i>
                            <span id="modalAction">เพิ่มชิ้นส่วนใหม่</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="partForm" class="needs-validation" novalidate action="part_process.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="part_id" id="partId" value="">
                            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                            
                            <div class="row g-4">
                                <!-- Part Information -->
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header" style="background-color: #f8f9fa;">
                                            <h6 class="mb-0 text-danger">
                                                <i class="fas fa-info-circle me-2"></i>
                                                ข้อมูลชิ้นส่วน
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">PartNo-PartName <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="partNo" name="part_no" required>
                                                <div class="invalid-feedback">กรุณาระบุรหัสชิ้นส่วน</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">หมายเหตุ</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Upload -->
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header" style="background-color: #f8f9fa;">
                                            <h6 class="mb-0 text-danger">
                                                <i class="fas fa-file-upload me-2"></i>
                                                อัพโหลดเอกสาร
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Step bending <small class="text-muted">(PDF)</small></label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="stepBending" name="step_bending" accept=".pdf">
                                                    <button class="btn btn-outline-danger" type="button" onclick="clearFile('stepBending')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Punch V-Die</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="punchVDie" name="punch_v_die">
                                                    <button class="btn btn-outline-danger" type="button" onclick="clearFile('punchVDie')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Drawing</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="drawing" name="drawing">
                                                    <button class="btn btn-outline-danger" type="button" onclick="clearFile('drawing')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">IQS</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="iqs" name="iqs">
                                                    <button class="btn btn-outline-danger" type="button" onclick="clearFile('iqs')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">ยืนยันการลบ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>คุณต้องการลบชิ้นส่วน <strong id="deletePartNo"></strong> ใช่หรือไม่?</p>
                        <p class="text-danger">การกระทำนี้ไม่สามารถเรียกคืนได้</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <form action="part_process.php" method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="part_id" id="deletePartId">
                            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                            <button type="submit" class="btn btn-danger">ลบ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            });
        });

        function searchParts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.getElementById('partsTableBody').getElementsByTagName('tr');
            
            Array.from(rows).forEach(row => {
                if (row.cells.length > 1) { // Skip "No data" row
                    const partNo = row.cells[1].textContent.toLowerCase();
                    row.style.display = partNo.includes(searchTerm) ? '' : 'none';
                }
            });
        }

        function openAddModal() {
            document.getElementById('modalAction').textContent = 'เพิ่มชิ้นส่วนใหม่';
            document.getElementById('partForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('partId').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('partModal'));
            modal.show();
        }

        function openEditModal(partId, partNo, notes) {
            document.getElementById('modalAction').textContent = 'แก้ไขชิ้นส่วน';
            document.getElementById('partForm').reset();
            document.getElementById('formAction').value = 'edit';
            document.getElementById('partId').value = partId;
            document.getElementById('partNo').value = partNo;
            document.getElementById('notes').value = notes;
            
            const modal = new bootstrap.Modal(document.getElementById('partModal'));
            modal.show();
        }

        function confirmDelete(partId, partNo) {
            document.getElementById('deletePartId').value = partId;
            document.getElementById('deletePartNo').textContent = partNo;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function clearFile(inputId) {
            document.getElementById(inputId).value = '';
        }

        // Form validation
        (function() {
            'use strict';
            
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
