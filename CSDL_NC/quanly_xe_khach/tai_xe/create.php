<?php
$page_title = "Thêm tài xế mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

if ($_POST) {
    $ma_tai_xe = trim(strtoupper($_POST['ma_tai_xe']));
    $ho_ten = trim($_POST['ho_ten']);
    $sdt = trim($_POST['sdt']);
    $ngay_sinh = $_POST['ngay_sinh'];
    $dia_chi = trim($_POST['dia_chi']);

    // Validation
    if (empty($ma_tai_xe) || empty($ho_ten) || empty($sdt) || empty($ngay_sinh) || empty($dia_chi)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } else {
        // Kiểm tra tuổi (phải từ 18-65 tuổi)
        $tuoi = date_diff(date_create($ngay_sinh), date_create())->y;
        if ($tuoi < 18) {
            $error = "Tài xế phải từ 18 tuổi trở lên!";
        } elseif ($tuoi > 65) {
            $error = "Tài xế không được quá 65 tuổi!";
        }
        // Kiểm tra số điện thoại
        elseif (!preg_match('/^(84|0[3|5|7|8|9])+([0-9]{8})$/', str_replace([' ', '-'], '', $sdt))) {
            $error = "Số điện thoại không hợp lệ!";
        } else {
            try {
                // Kiểm tra trùng mã tài xế
                $check_query = "SELECT COUNT(*) FROM tai_xe WHERE MaTaiXe = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$ma_tai_xe]);

                if ($check_stmt->fetchColumn() > 0) {
                    $error = "Mã tài xế đã tồn tại!";
                } else {
                    // Kiểm tra trùng số điện thoại
                    $check_sdt = "SELECT COUNT(*) FROM tai_xe WHERE SDT = ?";
                    $check_stmt_sdt = $db->prepare($check_sdt);
                    $check_stmt_sdt->execute([str_replace([' ', '-'], '', $sdt)]);

                    if ($check_stmt_sdt->fetchColumn() > 0) {
                        $error = "Số điện thoại đã được sử dụng!";
                    } else {
                        $query = "INSERT INTO tai_xe (MaTaiXe, HoTen, SDT, NgaySinh, DiaChi) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);

                        if ($stmt->execute([$ma_tai_xe, $ho_ten, str_replace([' ', '-'], '', $sdt), $ngay_sinh, $dia_chi])) {
                            $success = "Thêm tài xế thành công!";
                            $_POST = array();
                        } else {
                            $error = "Có lỗi xảy ra khi thêm tài xế!";
                        }
                    }
                }
            } catch(PDOException $e) {
                $error = "Lỗi: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Thêm tài xế mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Tài xế</a></li>
                        <li class="breadcrumb-item active">Thêm mới</li>
                    </ol>
                </nav>

                <!-- Thông báo -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-id-card me-1"></i>Mã tài xế <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ma_tai_xe"
                                   value="<?php echo isset($_POST['ma_tai_xe']) ? htmlspecialchars($_POST['ma_tai_xe']) : ''; ?>"
                                   placeholder="VD: TX001, TX002"
                                   style="text-transform: uppercase;"
                                   maxlength="10"
                                   required>
                            <div class="form-text">Mã tự động chuyển thành chữ hoa</div>
                            <div class="invalid-feedback">Vui lòng nhập mã tài xế</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-birthday-cake me-1"></i>Ngày sinh <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   class="form-control"
                                   name="ngay_sinh"
                                   value="<?php echo isset($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : ''; ?>"
                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                   min="<?php echo date('Y-m-d', strtotime('-65 years')); ?>"
                                   required>
                            <div class="form-text">Từ 18 đến 65 tuổi</div>
                            <div class="invalid-feedback">Vui lòng chọn ngày sinh hợp lệ</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Họ và tên <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               name="ho_ten"
                               value="<?php echo isset($_POST['ho_ten']) ? htmlspecialchars($_POST['ho_ten']) : ''; ?>"
                               placeholder="VD: Nguyễn Văn An"
                               maxlength="50"
                               required>
                        <div class="invalid-feedback">Vui lòng nhập họ tên</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-phone me-1"></i>Số điện thoại <span class="text-danger">*</span>
                            </label>
                            <input type="tel"
                                   class="form-control"
                                   name="sdt"
                                   value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : ''; ?>"
                                   placeholder="VD: 0987654321"
                                   pattern="^(84|0[3|5|7|8|9])+([0-9]{8})$"
                                   maxlength="15"
                                   required>
                            <div class="form-text">10-11 số, bắt đầu bằng 0 hoặc 84</div>
                            <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-info-circle me-1 text-info"></i>Tuổi hiện tại
                            </label>
                            <input type="text"
                                   class="form-control bg-light"
                                   id="tuoi_hien_tai"
                                   readonly
                                   placeholder="Tự động tính">
                            <div class="form-text">Được tính tự động từ ngày sinh</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>Địa chỉ <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                                  name="dia_chi"
                                  rows="3"
                                  placeholder="VD: 123 Đường ABC, Phường XYZ, Quận/Huyện, Tỉnh/TP"
                                  maxlength="200"
                                  required><?php echo isset($_POST['dia_chi']) ? htmlspecialchars($_POST['dia_chi']) : ''; ?></textarea>
                        <div class="invalid-feedback">Vui lòng nhập địa chỉ</div>
                    </div>

                    <!-- Thông tin bổ sung -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-info-circle me-2"></i>Yêu cầu tài xế
                                </h6>
                                <ul class="mb-0 small">
                                    <li>Có bằng lái xe hạng B2 trở lên</li>
                                    <li>Kinh nghiệm lái xe ít nhất 2 năm</li>
                                    <li>Sức khỏe tốt, không có tiền sử bệnh tim, cao huyết áp</li>
                                    <li>Không có tiền án tiền sử về giao thông</li>
                                    <li>Thái độ phục vụ tốt, trung thực</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Làm mới
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Lưu tài xế
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Tính tuổi tự động khi chọn ngày sinh
    document.querySelector('input[name="ngay_sinh"]').addEventListener('change', function() {
        const ngaySinh = new Date(this.value);
        const ngayHienTai = new Date();

        if (ngaySinh) {
            let tuoi = ngayHienTai.getFullYear() - ngaySinh.getFullYear();
            const monthDiff = ngayHienTai.getMonth() - ngaySinh.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && ngayHienTai.getDate() < ngaySinh.getDate())) {
                tuoi--;
            }

            document.getElementById('tuoi_hien_tai').value = tuoi + ' tuổi';

            // Validation tuổi
            if (tuoi < 18) {
                this.setCustomValidity('Tài xế phải từ 18 tuổi trở lên');
            } else if (tuoi > 65) {
                this.setCustomValidity('Tài xế không được quá 65 tuổi');
            } else {
                this.setCustomValidity('');
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
