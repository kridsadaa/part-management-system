</main>
<div class="footer-wrapper">
    <footer class="bottom-bar">
        <div class="footer-content">
            <div class="company-info">
                <strong>INFINITY PART CO.,LTD</strong>
                <div>บริษัท อินฟินิตี้ พาร์ท จำกัด</div>
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> สงวนลิขสิทธิ์
                </div>
            </div>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <div class="user-info">
                    <div>ผู้ใช้: <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                    <div>บทบาท: <?php echo ($_SESSION['role'] == 'admin') ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งานทั่วไป'; ?></div>
                </div>
            <?php endif; ?>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // เปิด/ปิดเมนูด้านข้าง
    document.getElementById('nav-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.querySelector('.menu-overlay').classList.toggle('active');
    });
    
    // ปิดเมนูเมื่อคลิกที่พื้นหลังทึบ
    document.querySelector('.menu-overlay').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('active');
        document.querySelector('.menu-overlay').classList.remove('active');
    });
</script>
</body>
</html>
