<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูลและฟังก์ชันการตรวจสอบสิทธิ์
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/file_utils.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// ตัวแปรสำหรับการกรองข้อมูล
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_company = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$filter_part = isset($_GET['part_id']) ? (int)$_GET['part_id'] : 0;
$filter_file_type = isset($_GET['file_type_id']) ? (int)$_GET['file_type_id'] : 0;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';

// ตัวแปรสำหรับการแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

// ดึงข้อมูลประวัติการเข้าดูไฟล์
$sql = "SELECT l.log_id, l.file_id, l.user_id, l.access_time, l.access_type, l.ip_address, 
        u.username, u.first_name, u.last_name, 
        c.company_name, 
        p.part_no, 
        f.file_name, f.file_size, f.file_type_id,
        ft.type_name as file_type_name
        FROM file_access_logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        LEFT JOIN files f ON l.file_id = f.file_id
        LEFT JOIN parts p ON f.part_id = p.part_id
        LEFT JOIN companies c ON p.company_id = c.company_id
        LEFT JOIN file_types ft ON f.file_type_id = ft.file_type_id
        WHERE l.access_type = 'view'";

// เพิ่มเงื่อนไขการกรอง
if ($filter_user) {
    $sql .= " AND l.user_id = $filter_user";
}
if ($filter_company) {
    $sql .= " AND c.company_id = $filter_company";
}
if ($filter_part) {
    $sql .= " AND p.part_id = $filter_part";
}
if ($filter_file_type) {
    $sql .= " AND f.file_type_id = $filter_file_type";
}
if ($filter_date_from) {
    $sql .= " AND CONVERT(DATE, l.access_time) >= '$filter_date_from'";
}
if ($filter_date_to) {
    $sql .= " AND CONVERT(DATE, l.access_time) <= '$filter_date_to'";
}

// นับจำนวนรายการทั้งหมด
$count_sql = "SELECT COUNT(*) as total FROM ($sql) as filtered_logs";
$count_result = executeQuery($count_sql);
$count_row = sqlsrv_fetch_array($count_result, SQLSRV_FETCH_ASSOC);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// เพิ่มการเรียงลำดับและการแบ่งหน้า (MSSQL ใช้ OFFSET FETCH แทน LIMIT)
$sql .= " ORDER BY l.access_time DESC OFFSET $offset ROWS FETCH NEXT $records_per_page ROWS ONLY";
$result = executeQuery($sql);

// ดึงข้อมูลสรุปจำนวนการเข้าดูไฟล์แต่ละประเภท
$summary_sql = "SELECT l.access_type, COUNT(*) as count
                FROM file_access_logs l
                LEFT JOIN files f ON l.file_id = f.file_id
                LEFT JOIN parts p ON f.part_id = p.part_id
                LEFT JOIN companies c ON p.company_id = c.company_id
                WHERE l.access_type = 'view'";

// เพิ่มเงื่อนไขการกรอง
if ($filter_user) {
    $summary_sql .= " AND l.user_id = $filter_user";
}
if ($filter_company) {
    $summary_sql .= " AND c.company_id = $filter_company";
}
if ($filter_part) {
    $summary_sql .= " AND p.part_id = $filter_part";
}
if ($filter_file_type) {
    $summary_sql .= " AND f.file_type_id = $filter_file_type";
}
if ($filter_date_from) {
    $summary_sql .= " AND CONVERT(DATE, l.access_time) >= '$filter_date_from'";
}
if ($filter_date_to) {
    $summary_sql .= " AND CONVERT(DATE, l.access_time) <= '$filter_date_to'";
}

$summary_sql .= " GROUP BY l.access_type";
$summary_result = executeQuery($summary_sql);

// สร้างอาร์เรย์สำหรับเก็บข้อมูลสรุป
$summary = [
    'view' => 0
];

// นำข้อมูลที่ได้มาเก็บในอาร์เรย์
while ($row = sqlsrv_fetch_array($summary_result, SQLSRV_FETCH_ASSOC)) {
    $type = $row['access_type'];
    $count = $row['count'];
    
    if (isset($summary[$type])) {
        $summary[$type] = $count;
    }
}

// ดึงข้อมูลผู้ใช้สำหรับตัวกรอง
$sql_users = "SELECT user_id, username, first_name, last_name FROM users ORDER BY first_name";
$result_users = executeQuery($sql_users);

// ดึงข้อมูลบริษัทสำหรับตัวกรอง
$sql_companies = "SELECT company_id, company_name FROM companies ORDER BY company_name";
$result_companies = executeQuery($sql_companies);

// ดึงข้อมูลประเภทไฟล์สำหรับตัวกรอง
$sql_file_types = "SELECT file_type_id, type_name as file_type_name FROM file_types ORDER BY file_type_name";
$result_file_types = executeQuery($sql_file_types);

// ตัวแปรสำหรับเก็บข้อมูลชิ้นส่วนสำหรับตัวกรอง
$parts = [];

// ถ้ามีการเลือกบริษัท ให้ดึงข้อมูลชิ้นส่วนของบริษัทนั้น
if ($filter_company > 0) {
    $sql_parts = "SELECT part_id, part_no FROM parts WHERE company_id = $filter_company ORDER BY part_no";
    $result_parts = executeQuery($sql_parts);
    
    while ($row = sqlsrv_fetch_array($result_parts, SQLSRV_FETCH_ASSOC)) {
        $parts[] = $row;
    }
}

// ประเภทการกระทำสำหรับตัวกรอง
$action_types = [
    'view' => 'ดู'
];

// ฟังก์ชันสำหรับแปลงประเภทการเข้าถึงเป็นข้อความภาษาไทย
function getAccessTypeText($accessType) {
    switch ($accessType) {
        case 'view':
            return 'ดู';
        default:
            return $accessType;
    }
}

// ฟังก์ชันสำหรับกำหนดคลาส CSS ของป้ายกำกับประเภทการเข้าถึง
function getAccessTypeBadgeClass($accessType) {
    switch ($accessType) {
        case 'view':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// ฟังก์ชันสำหรับแปลงประเภทไฟล์เป็นไอคอน
function getFileTypeIcon($fileType) {
    switch (strtolower($fileType)) {
        case 'step bending':
            return '<i class="fas fa-bezier-curve text-danger me-1 fa-lg"></i>';
        case 'punch v-die':
            return '<i class="fas fa-tools text-danger me-1 fa-lg"></i>';
        case 'drawing':
            return '<i class="fas fa-drafting-compass text-danger me-1 fa-lg"></i>';
        case 'iqs':
            return '<i class="fas fa-clipboard-check text-danger me-1 fa-lg"></i>';
        default:
            return '<i class="fas fa-file text-danger me-1 fa-lg"></i>';
    }
}

// ฟังก์ชันสำหรับแปลงขนาดไฟล์เป็นข้อความที่อ่านง่าย
function formatFileSize($size) {
    if (!$size) {
        return 'N/A';
    }
    
    if ($size < 1024) {
        return $size . ' bytes';
    } elseif ($size < 1048576) {
        return round($size / 1024, 2) . ' KB';
    } elseif ($size < 1073741824) {
        return round($size / 1048576, 2) . ' MB';
    } else {
        return round($size / 1073741824, 2) . ' GB';
    }
}

// HTML header
$pageTitle = "ประวัติการเข้าดูไฟล์";
include 'header.php';

?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> ประวัติการเข้าดูไฟล์</h5>
                </div>
                <div class="card-body">
                    <!-- สรุปข้อมูลการเข้าดูไฟล์ -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-eye"></i> การดูไฟล์</h5>
                                    <h3 class="mb-0"><?php echo number_format($summary['view']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- กราฟแสดงกิจกรรมการเข้าดูไฟล์ -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> กิจกรรมการเข้าดูไฟล์</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="accessActivityChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ส่วนตัวกรอง -->
                    <div class="filter-section mb-4">
                        <form method="GET" action="access_logs.php" class="row g-3">
                            <div class="col-md-2">
                                <label for="user_id" class="form-label">ผู้ใช้</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="0">ทั้งหมด</option>
                                    <?php while ($user = sqlsrv_fetch_array($result_users, SQLSRV_FETCH_ASSOC)): ?>
                                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($filter_user == $user['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="company_id" class="form-label">บริษัท</label>
                                <select class="form-select" id="company_id" name="company_id" onchange="this.form.submit()">
                                    <option value="0">ทั้งหมด</option>
                                    <?php while ($company = sqlsrv_fetch_array($result_companies, SQLSRV_FETCH_ASSOC)): ?>
                                        <option value="<?php echo $company['company_id']; ?>" <?php echo ($filter_company == $company['company_id']) ? 'selected' : ''; ?>>
                                            <?php echo $company['company_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="part_id" class="form-label">Part No.</label>
                                <select class="form-select" id="part_id" name="part_id" <?php echo (empty($parts)) ? 'disabled' : ''; ?>>
                                    <option value="0">ทั้งหมด</option>
                                    <?php foreach ($parts as $part): ?>
                                        <option value="<?php echo $part['part_id']; ?>" <?php echo ($filter_part == $part['part_id']) ? 'selected' : ''; ?>>
                                            <?php echo $part['part_no']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="file_type_id" class="form-label">ประเภทไฟล์</label>
                                <select class="form-select" id="file_type_id" name="file_type_id">
                                    <option value="0">ทั้งหมด</option>
                                    <?php while ($file_type = sqlsrv_fetch_array($result_file_types, SQLSRV_FETCH_ASSOC)): ?>
                                        <option value="<?php echo $file_type['file_type_id']; ?>" <?php echo ($filter_file_type == $file_type['file_type_id']) ? 'selected' : ''; ?>>
                                            <?php echo $file_type['file_type_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                          
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">ตั้งแต่วันที่</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">ถึงวันที่</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>">
                            </div>
                            <div class="col-md-12 mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i> กรอง
                                </button>
                                <a href="access_logs.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-redo me-2"></i> รีเซ็ต
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- ตารางแสดงประวัติการเข้าดูไฟล์ -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>วันที่และเวลา</th>
                                  
                                    <th>ผู้ใช้</th>
                                    <th>บริษัท</th>
                                    <th>Part No.</th>
                                    <th>ไฟล์</th>
                                    <th>ชื่อไฟล์</th>
                                    <th>ขนาด</th>
                                    <th>ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = ($page - 1) * $records_per_page + 1;
                                if ($result && sqlsrv_has_rows($result)):
                                    while ($log = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php 
                                        // จัดการวันที่ในรูปแบบ MSSQL
                                        $access_time = $log['access_time'];
                                        if ($access_time instanceof DateTime) {
                                            echo $access_time->format('d/m/Y H:i:s');
                                        } else {
                                            echo date('d/m/Y H:i:s', strtotime($access_time));
                                        }
                                    ?></td>
                                 
                                    <td>
                                     
                                            <?php echo $log['first_name'] . ' ' . $log['last_name']; ?>
                                            <small class="text-muted">(<?php echo $log['username']; ?>)</small>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo $log['company_name']; ?>
                                    </td>
                                    <td>
                                        <?php echo $log['part_no']; ?>
                                    </td>
                                    <td>
                                        <a href="access_logs.php?file_type_id=<?php echo $log['file_type_id']; ?>" title="<?php echo $log['file_type_name']; ?>">
                                            <?php echo getFileTypeIcon($log['file_type_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $log['file_name']; ?></td>
                                    <td><?php echo formatFileSize($log['file_size']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_file.php?id=<?php echo $log['file_id']; ?>" class="btn btn-outline-primary" title="ดูไฟล์" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">ไม่พบข้อมูลประวัติการเข้าดูไฟล์</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- ส่วนการแบ่งหน้า -->
                    <div class="pagination-section mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="access_logs.php?page=<?php echo ($page - 1); ?>&user_id=<?php echo $filter_user; ?>&company_id=<?php echo $filter_company; ?>&part_id=<?php echo $filter_part; ?>&file_type_id=<?php echo $filter_file_type; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&action=<?php echo $filter_action; ?>">
                                        <i class="fas fa-chevron-left me-2"></i> ก่อนหน้า
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php
                                // แสดงเฉพาะหน้าที่อยู่ใกล้กับหน้าปัจจุบัน
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                // สร้างพารามิเตอร์สำหรับ URL
                                $url_params = "user_id={$filter_user}&company_id={$filter_company}&part_id={$filter_part}&file_type_id={$filter_file_type}&date_from={$filter_date_from}&date_to={$filter_date_to}&action={$filter_action}";
                                
                                // แสดงหน้าแรก
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="access_logs.php?page=1&' . $url_params . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }
                                
                                // แสดงหน้าที่อยู่ใกล้กับหน้าปัจจุบัน
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="access_logs.php?page=' . $i . '&' . $url_params . '">' . $i . '</a></li>';
                                }
                                
                                // แสดงหน้าสุดท้าย
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="access_logs.php?page=' . $total_pages . '&' . $url_params . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="access_logs.php?page=<?php echo ($page + 1); ?>&<?php echo $url_params; ?>">
                                        ถัดไป <i class="fas fa-chevron-right ms-2"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    
                    <!-- แสดงสรุปข้อมูล -->
                    <div class="mt-3 text-center">
                        <p class="text-muted">
                            แสดง <?php echo min($total_records, $records_per_page); ?> รายการ จากทั้งหมด <?php echo $total_records; ?> รายการ
                            (หน้า <?php echo $page; ?> จาก <?php echo $total_pages; ?>)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // เปิด/ปิดเมนูด้านข้าง
    if (document.getElementById('nav-toggle')) {
        document.getElementById('nav-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.menu-overlay').classList.toggle('active');
        });
    }
    
    // ปิดเมนูเมื่อคลิกที่พื้นหลังทึบ
    if (document.querySelector('.menu-overlay')) {
        document.querySelector('.menu-overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.querySelector('.menu-overlay').classList.remove('active');
        });
    }
    
    // JavaScript สำหรับการอัปเดตตัวเลือกชิ้นส่วนเมื่อเลือกบริษัท
    document.getElementById('company_id').addEventListener('change', function() {
        document.getElementById('part_id').disabled = this.value == 0;
    });
    
    // ปุ่มสำหรับเลือกช่วงวันที่
    const btnToday = document.createElement('button');
    btnToday.type = 'button';
    btnToday.className = 'btn btn-sm btn-outline-secondary me-1';
    btnToday.textContent = 'วันนี้';
    btnToday.addEventListener('click', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date_from').value = today;
        document.getElementById('date_to').value = today;
    });
    
    const btnYesterday = document.createElement('button');
    btnYesterday.type = 'button';
    btnYesterday.className = 'btn btn-sm btn-outline-secondary me-1';
    btnYesterday.textContent = 'เมื่อวาน';
    btnYesterday.addEventListener('click', function() {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toISOString().split('T')[0];
        document.getElementById('date_from').value = yesterdayStr;
        document.getElementById('date_to').value = yesterdayStr;
    });
    
    const btnThisWeek = document.createElement('button');
    btnThisWeek.type = 'button';
    btnThisWeek.className = 'btn btn-sm btn-outline-secondary me-1';
    btnThisWeek.textContent = 'สัปดาห์นี้';
    btnThisWeek.addEventListener('click', function() {
        const today = new Date();
        const dayOfWeek = today.getDay();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - dayOfWeek);
        
        document.getElementById('date_from').value = startOfWeek.toISOString().split('T')[0];
        document.getElementById('date_to').value = today.toISOString().split('T')[0];
    });
    
    const btnThisMonth = document.createElement('button');
    btnThisMonth.type = 'button';
    btnThisMonth.className = 'btn btn-sm btn-outline-secondary me-1';
    btnThisMonth.textContent = 'เดือนนี้';
    btnThisMonth.addEventListener('click', function() {
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        document.getElementById('date_from').value = startOfMonth.toISOString().split('T')[0];
        document.getElementById('date_to').value = today.toISOString().split('T')[0];
    });
    
    // เพิ่มปุ่มเข้าไปในหน้า
    const dateContainer = document.createElement('div');
    dateContainer.className = 'date-shortcuts mt-2';
    dateContainer.appendChild(btnToday);
    dateContainer.appendChild(btnYesterday);
    dateContainer.appendChild(btnThisWeek);
    dateContainer.appendChild(btnThisMonth);
    
    // แทรกปุ่มหลังจากช่องกรอกวันที่
    const dateToField = document.getElementById('date_to');
    if (dateToField) {
        dateToField.parentNode.appendChild(dateContainer);
    }
    
    // สร้างกราฟแสดงกิจกรรมการเข้าดูไฟล์
    if (document.getElementById('accessActivityChart')) {
        // ดึงข้อมูลกิจกรรมการเข้าดูไฟล์ในช่วง 7 วันล่าสุด
        fetch('api/get_access_activity.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // ตรวจสอบว่ามีข้อมูลหรือไม่
                if (data.error) {
                    document.getElementById('accessActivityChart').parentNode.innerHTML = '<div class="alert alert-warning">' + data.error + '</div>';
                    return;
                }
                
                const ctx = document.getElementById('accessActivityChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'การดูไฟล์',
                                data: data.view,
                                borderColor: 'rgba(23, 162, 184, 1)',
                                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                                borderWidth: 2,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'กิจกรรมการเข้าดูไฟล์ในช่วง 7 วันล่าสุด'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching access activity data:', error);
                document.getElementById('accessActivityChart').parentNode.innerHTML = '<div class="alert alert-warning">ไม่สามารถโหลดข้อมูลกราฟได้</div>';
            });
    }
});
</script>
