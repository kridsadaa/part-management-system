const navToggle = document.getElementById('nav-toggle');
const menuIcon = document.getElementById('menu-icon');
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('main');
const header = document.querySelector('header');
const menuOverlay = document.querySelector('.menu-overlay');

// ตั้งค่าเริ่มต้นตามขนาดหน้าจอ
let isMenuOpen = window.innerWidth > 768;

function toggleMenu() {
    isMenuOpen = !isMenuOpen;
    
    // สลับไอคอน
    menuIcon.classList.toggle('fa-bars');
    menuIcon.classList.toggle('fa-times');
    
    // สลับ class สำหรับ animation
    document.body.classList.toggle('nav-collapsed');
    
    // ป้องกันการเลื่อนหน้าเมื่อเมนูเปิดบนมือถือ
    if (window.innerWidth <= 768) {
        document.body.style.overflow = isMenuOpen ? 'hidden' : '';
    }
}

// เพิ่ม Event Listeners
navToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMenu();
});

// ปิดเมนูเมื่อคลิกที่ overlay
if (menuOverlay) {
    menuOverlay.addEventListener('click', () => {
        if (isMenuOpen && window.innerWidth <= 768) {
            toggleMenu();
        }
    });
}

// จัดการเมื่อขนาดหน้าจอเปลี่ยน
function handleResize() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // รีเซ็ตสถานะบนมือถือ
        document.body.style.overflow = '';
        if (isMenuOpen) {
            isMenuOpen = false;
            document.body.classList.remove('nav-collapsed');
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }
    } else {
        // จัดการบน Desktop
        document.body.style.overflow = '';
        document.body.classList.remove('nav-collapsed');
        isMenuOpen = true;
    }
}

// รองรับการปัดนิ้วบนมือถือ
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const SWIPE_THRESHOLD = 50;
    const swipeDistance = touchEndX - touchStartX;
    
    if (window.innerWidth <= 768 && Math.abs(swipeDistance) > SWIPE_THRESHOLD) {
        if (swipeDistance > 0 && !isMenuOpen) {
            // ปัดขวาเพื่อเปิดเมนู
            toggleMenu();
        } else if (swipeDistance < 0 && isMenuOpen) {
            // ปัดซ้ายเพื่อปิดเมนู
            toggleMenu();
        }
    }
}

// เริ่มต้นการทำงาน
window.addEventListener('resize', handleResize);
window.addEventListener('load', handleResize);

// Parts management functions
function searchParts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.getElementById('partsTableBody').getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const partNo = row.getElementsByTagName('td')[1].textContent.toLowerCase();
        row.style.display = partNo.includes(searchTerm) ? '' : 'none';
    });
}

function openAddModal() {
    document.getElementById('modalAction').textContent = 'เพิ่มชิ้นส่วนใหม่';
    document.getElementById('partForm').reset();
    document.getElementById('partForm').dataset.mode = 'add';
    document.getElementById('partForm').dataset.partId = '';
    const modal = new bootstrap.Modal(document.getElementById('partModal'));
    modal.show();
}

function openEditModal(partId) {
    document.getElementById('modalAction').textContent = 'แก้ไขชิ้นส่วน';
    document.getElementById('partForm').reset();
    document.getElementById('partForm').dataset.mode = 'edit';
    document.getElementById('partForm').dataset.partId = partId;

    // TODO: Fetch part data from server and populate form
    document.getElementById('partNo').value = partId;
    
    const modal = new bootstrap.Modal(document.getElementById('partModal'));
    modal.show();
}

function savePart() {
    const form = document.getElementById('partForm');
    const mode = form.dataset.mode;
    const partId = form.dataset.partId;
    
    // Get form values
    const partNo = document.getElementById('partNo').value;
    const stepBending = document.getElementById('stepBending').files[0];
    const punchVDie = document.getElementById('punchVDie').files[0];
    const drawing = document.getElementById('drawing').files[0];
    const iqs = document.getElementById('iqs').files[0];

    if (mode === 'edit') {
        // TODO: Send update request to server
        // For now, we'll just update the table row
        const rows = document.getElementById('partsTableBody').getElementsByTagName('tr');
        Array.from(rows).forEach(row => {
            const currentPartNo = row.getElementsByTagName('td')[1].textContent;
            if (currentPartNo === partId) {
                row.getElementsByTagName('td')[1].textContent = partNo;
                // Update file indicators if new files were selected
                if (stepBending) row.getElementsByTagName('td')[2].querySelector('a').classList.remove('disabled');
                if (punchVDie) row.getElementsByTagName('td')[3].querySelector('a').classList.remove('disabled');
                if (drawing) row.getElementsByTagName('td')[4].querySelector('a').classList.remove('disabled');
                if (iqs) row.getElementsByTagName('td')[5].querySelector('a').classList.remove('disabled');
            }
        });
    } else {
        // Add new row
        const tbody = document.getElementById('partsTableBody');
        const rowCount = tbody.getElementsByTagName('tr').length;
        
        const newRow = `
            <tr>
                <td>${rowCount + 1}</td>
                <td>${partNo}</td>
                <td><a href="#" class="btn btn-sm btn-danger ${!stepBending ? 'disabled' : ''}"><i class="fas fa-file-pdf"></i></a></td>
                <td><a href="#" class="btn btn-sm btn-secondary ${!punchVDie ? 'disabled' : ''}"><i class="fas fa-tools"></i></a></td>
                <td><a href="#" class="btn btn-sm btn-info ${!drawing ? 'disabled' : ''}"><i class="fas fa-drafting-compass"></i></a></td>
                <td><a href="#" class="btn btn-sm btn-dark ${!iqs ? 'disabled' : ''}"><i class="fas fa-file-alt"></i></a></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="openEditModal('${partNo}')"><i class="fas fa-edit"></i> แก้ไข</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-trash"></i> Delete</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
        
        tbody.insertAdjacentHTML('beforeend', newRow);
    }
    
    // Close the modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('partModal'));
    modal.hide();
}

// ฟังก์ชันสำหรับจัดการไฟล์
function confirmDeleteFile(fileId, partId, fileName) {
    if (confirm(`คุณต้องการลบไฟล์ "${fileName}" ใช่หรือไม่?`)) {
        // สร้าง form สำหรับส่งข้อมูลลบไฟล์
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'file_delete.php';
        
        // สร้าง input สำหรับ file_id
        const fileIdInput = document.createElement('input');
        fileIdInput.type = 'hidden';
        fileIdInput.name = 'file_id';
        fileIdInput.value = fileId;
        form.appendChild(fileIdInput);
        
        // สร้าง input สำหรับ part_id
        const partIdInput = document.createElement('input');
        partIdInput.type = 'hidden';
        partIdInput.name = 'part_id';
        partIdInput.value = partId;
        form.appendChild(partIdInput);
        
        // เพิ่ม form ไปยัง body และส่งข้อมูล
        document.body.appendChild(form);
        form.submit();
    }
}

// ฟังก์ชันสำหรับแสดงตัวอย่างไฟล์ก่อนอัพโหลด
function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        // ล้างข้อความเดิม
        preview.innerHTML = '';
        
        // ตรวจสอบประเภทไฟล์
        if (file.type === 'application/pdf') {
            // สร้างไอคอน PDF
            const icon = document.createElement('i');
            icon.className = 'fas fa-file-pdf fa-3x text-danger';
            preview.appendChild(icon);
            
            // สร้างข้อความแสดงชื่อไฟล์
            const fileName = document.createElement('p');
            fileName.className = 'mt-2 mb-0';
            fileName.textContent = file.name;
            preview.appendChild(fileName);
            
            // สร้างข้อความแสดงขนาดไฟล์
            const fileSize = document.createElement('small');
            fileSize.className = 'text-muted';
            fileSize.textContent = formatFileSize(file.size);
            preview.appendChild(fileSize);
        } else if (file.type.startsWith('image/')) {
            // สร้างตัวอย่างรูปภาพ
            const img = document.createElement('img');
            img.className = 'img-thumbnail';
            img.style.maxHeight = '150px';
            preview.appendChild(img);
            
            // อ่านไฟล์และแสดงตัวอย่าง
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
            
            // สร้างข้อความแสดงชื่อไฟล์
            const fileName = document.createElement('p');
            fileName.className = 'mt-2 mb-0';
            fileName.textContent = file.name;
            preview.appendChild(fileName);
            
            // สร้างข้อความแสดงขนาดไฟล์
            const fileSize = document.createElement('small');
            fileSize.className = 'text-muted';
            fileSize.textContent = formatFileSize(file.size);
            preview.appendChild(fileSize);
        } else {
            // สร้างไอคอนไฟล์ทั่วไป
            const icon = document.createElement('i');
            icon.className = 'fas fa-file fa-3x text-primary';
            preview.appendChild(icon);
            
            // สร้างข้อความแสดงชื่อไฟล์
            const fileName = document.createElement('p');
            fileName.className = 'mt-2 mb-0';
            fileName.textContent = file.name;
            preview.appendChild(fileName);
            
            // สร้างข้อความแสดงขนาดไฟล์
            const fileSize = document.createElement('small');
            fileSize.className = 'text-muted';
            fileSize.textContent = formatFileSize(file.size);
            preview.appendChild(fileSize);
        }
    } else {
        // ถ้าไม่มีไฟล์ที่เลือก
        preview.innerHTML = '<p class="text-muted">ไม่มีไฟล์ที่เลือก</p>';
    }
}

// ฟังก์ชันสำหรับแปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ฟังก์ชันสำหรับการยืนยันการลบชิ้นส่วน
function confirmDeletePart(partId, partNo) {
    if (confirm(`คุณต้องการลบชิ้นส่วน "${partNo}" ใช่หรือไม่? การลบชิ้นส่วนจะทำให้ไฟล์ที่เกี่ยวข้องทั้งหมดถูกลบไปด้วย`)) {
        // สร้าง form สำหรับส่งข้อมูลลบชิ้นส่วน
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'part_process.php';
        
        // สร้าง input สำหรับ action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        // สร้าง input สำหรับ part_id
        const partIdInput = document.createElement('input');
        partIdInput.type = 'hidden';
        partIdInput.name = 'part_id';
        partIdInput.value = partId;
        form.appendChild(partIdInput);
        
        // เพิ่ม form ไปยัง body และส่งข้อมูล
        document.body.appendChild(form);
        form.submit();
    }
}

// เพิ่ม Event Listener เมื่อโหลดหน้าเว็บ
document.addEventListener('DOMContentLoaded', function() {
    // Event Listener สำหรับการค้นหาชิ้นส่วน
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', searchParts);
    }
    
    // Event Listener สำหรับการแสดงตัวอย่างไฟล์
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        const previewId = input.getAttribute('data-preview');
        if (previewId) {
            input.addEventListener('change', function() {
                previewFile(this, previewId);
            });
        }
    });
    
    // แสดงข้อความแจ้งเตือนและซ่อนหลังจาก 5 วินาที
    const alertMessages = document.querySelectorAll('.alert');
    if (alertMessages.length > 0) {
        setTimeout(function() {
            alertMessages.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    }
});
