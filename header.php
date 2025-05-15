<?php
// ตรวจสอบว่ามีการเริ่มเซสชันแล้วหรือไม่
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INFINITY PART CO.,LTD - <?php echo isset($pageTitle) ? $pageTitle : 'บริษัท อินฟินิตี้ พาร์ท จำกัด'; ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
