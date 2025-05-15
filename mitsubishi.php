<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MITSUBISHI - INFINITY PART CO.,LTD</title>
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
    <div class="row mb-4">
        <div class="col">
            <h2>Mitsubishi</h2>
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                <button class="btn btn-primary" onclick="searchParts()">
                    <i class="fas fa-search"></i> Search
                </button>
                <button class="btn btn-success ms-2" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add file
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Part No.</th>
                    <th>Step bending</th>
                    <th>Punch V-Die</th>
                    <th>Drawing</th>
                    <th>IQS</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="partsTableBody">
                <tr>
                    <td>1</td>
                    <td>VR02DG54G03</td>
                    <td><a href="#" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i></a></td>
                    <td><a href="#" class="btn btn-sm btn-secondary"><i class="fas fa-tools"></i></a></td>
                    <td><a href="#" class="btn btn-sm btn-info"><i class="fas fa-drafting-compass"></i></a></td>
                    <td><a href="#" class="btn btn-sm btn-dark"><i class="fas fa-file-alt"></i></a></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="openEditModal('VR02DG54G03')"><i class="fas fa-edit"></i> แก้ไข</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-trash"></i> Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>1</td>
                    <td>VR02DG54G04</td>
                    <td><a href="#" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i></a></td>
                    <td><a href="#" class="btn btn-sm btn-secondary"><i class="fas fa-tools"></i></a></td>
                    <td><a href="#" class="btn btn-sm btn-info"><i class="fas fa-drafting-compass"></i></a></td>
                    <td><a href="#" class="btn btn-sm btn-dark"><i class="fas fa-file-alt"></i></a></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="openEditModal('VR02DG54G03')"><i class="fas fa-edit"></i> แก้ไข</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-trash"></i> Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <!-- More rows will be added dynamically -->
            </tbody>
        </table>
    </div>
</div>

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
                <form id="partForm" class="needs-validation" novalidate>
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
                                        <label class="form-label">รหัสชิ้นส่วน <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="partNo" required>
                                        <div class="invalid-feedback">กรุณาระบุรหัสชิ้นส่วน</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">หมายเหตุ</label>
                                        <textarea class="form-control" id="notes" rows="3"></textarea>
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
                                            <input type="file" class="form-control" id="stepBending" accept=".pdf">
                                            <button class="btn btn-outline-danger" type="button" onclick="clearFile('stepBending')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Punch V-Die</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="punchVDie">
                                            <button class="btn btn-outline-danger" type="button" onclick="clearFile('punchVDie')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Drawing</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="drawing">
                                            <button class="btn btn-outline-danger" type="button" onclick="clearFile('drawing')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">IQS</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="iqs">
                                            <button class="btn btn-outline-danger" type="button" onclick="clearFile('iqs')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="background-color: #f8f9fa;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>ยกเลิก
                </button>
                <button type="button" class="btn px-4 text-white" onclick="savePart()" style="background-color: #e60012;">
                    <i class="fas fa-save me-2"></i>บันทึก
                </button>
            </div>
        </div>
    </div>
</div>

        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
