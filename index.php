<?php
// เริ่มเซสชัน
session_start();

// รวมไฟล์การเชื่อมต่อฐานข้อมูล
require_once 'config/database.php';

// ตรวจสอบการเข้าสู่ระบบ (ถ้าต้องการให้ต้องเข้าสู่ระบบก่อนเข้าหน้านี้)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// ดึงข้อมูลบริษัททั้งหมดจากฐานข้อมูล
$sql = "SELECT * FROM companies ORDER BY company_id";
$result = executeQuery($sql);
$companies = [];

if ($result && sqlsrv_has_rows($result)) {
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        // จัดการกับค่าวันที่จาก MSSQL
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
        
        $companies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INFINITY PART CO.,LTD - บริษัท อินฟินิตี้ พาร์ท จำกัด</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="menu-overlay"></div>
    <?php include 'nav.php'; ?>
    
    <header>
        <div class="header-left">
            
        </div>
        <div class="header-right">
            <div class="company-name">INFINITY PART CO.,LTD</div>
            <div class="company-name-th">บริษัท อินฟินิตี้ พาร์ท จำกัด</div>
        </div>
    </header>

    <main>
        <div class="brand-grid">
            <?php if (empty($companies)): ?>
                <div class="no-data">ไม่พบข้อมูลบริษัท</div>
            <?php else: ?>
                <?php foreach ($companies as $company): ?>
                    <a href="company.php?id=<?php echo $company['company_id']; ?>" class="brand-item-link">
                        <div class="brand-item">
                            <img src="images/<?php echo strtolower($company['company_name']); ?>.png" alt="<?php echo $company['company_name']; ?>" onerror="this.src='images/placeholder.png'">
                            <p><?php echo $company['company_name']; ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>
