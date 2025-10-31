<?php
$page_title = "Chỉnh sửa tài xế";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin tài xế hiện tại
try {
    $query = "SELECT * FROM tai_xe WHERE MaTaiXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $tai_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tai_xe) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $ho_ten = trim($_POST['ho_ten']);
    $sdt = trim($_POST['sdt']);
    $ngay_sinh = $_POST['ngay_sinh'];
    $dia_chi = trim($_POST['dia_chi']);

    // Validation
    if (empty($ho_ten) || empty($sdt) || empty($ngay_sinh) || empty($dia_chi)) {
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
                // Kiểm tra trùng số điện thoại (trừ chính nó)
                $check_sdt = "SELECT COUNT(*) FROM tai_xe WHERE SDT = ? AND MaTaiXe != ?";
                $check_stmt_sdt = $db->prepare($check_sdt);
                $check_stmt_sdt->execute([str_replace([' ', '-'], '', $sdt), $id]);

                if ($check_stmt_sdt->fetchColumn() > 0) {
                    $error = "Số điện thoại đã được sử dụng bởi tài xế khác!";
                } else {
                    $query = "UPDATE tai_xe SET HoTen = ?, SDT = ?, NgaySinh = ?, DiaChi = ? WHERE MaTaiXe = ?";
                    $stmt = $db->prepare($query);

                    if ($stmt->execute([$ho_ten, str_replace([' ', '-'], '', $sdt), $ngay_sinh, $dia_chi, $id])) {
                        $success = "Cập nhật tài xế thành công!";
                        // Cập nhật lại thông tin
                        $tai_xe['HoTen'] = $ho_ten;
                        $tai_xe['SDT'] = str_replace([' ', '-'], '', $sdt);
                        $tai_xe['NgaySinh'] = $ngay_sinh;
                        $tai_xe['DiaChi'] = $dia_chi;
                    } else {
                        $error = "Có lỗi xảy ra khi cập nhật tài xế!";
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
                    <i class="fas fa-edit text-warning me-2"></i>
                    Chỉnh sửa tài xế
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Tài xế</a></li>
                        <li class="breadcrumb-item active">Chỉnh sửa</li>
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

                <?php if ($tai_xe): ?>
                    <!-- Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-id-card me-1"></i>Mã tài xế
                                </label>
                                <input type="text"
                                       class="form-control bg-light"
                                       value="<?php echo htmlspecialchars($tai_xe['MaTaiXe']); ?>"
                                       readonly>
                                <div class="form-text text-info">Mã tài xế không thể thay đổi</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-birthday-cake me-1"></i>Ngày sinh <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control"
                                       name="ngay_sinh"
                                       value="<?php echo isset($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : $tai_xe['NgaySinh']; ?>"
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
                                   value="<?php echo isset($_POST['ho_ten']) ? htmlspecialchars($_POST['ho_ten']) : htmlspecialchars($tai_xe['HoTen']); ?>"
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
                                       value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : htmlspecialchars($tai_xe['SDT']); ?>"
                                       pattern="^(84|0[3|5|7|8|9])+([0-9]{8})$"
                                       maxlength="15"
                                       required>
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
                                       value="<?php echo date_diff(date_create($tai_xe['NgaySinh']), date_create())->y; ?> tuổi">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Địa chỉ <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control"
                                      name="dia_chi"
                                      rows="3"
                                      maxlength="200"
                                      required><?php echo isset($_POST['dia_chi']) ? htmlspecialchars($_POST['dia_chi']) : htmlspecialchars($tai_xe['DiaChi']); ?></textarea>
                            <div class="invalid-feedback">Vui lòng nhập địa chỉ</div>
                        </div>

                        <!-- Thông tin hiện tại -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle text-info me-2"></i>Thông tin hiện tại
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Mã tài xế:</strong><br>
                                            <span class="text-primary"><?php echo htmlspecialchars($tai_xe['MaTaiXe']); ?></span><br><br>
                                            <strong>Họ tên:</strong><br>
                                            <span><?php echo htmlspecialchars($tai_xe['HoTen']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Số điện thoại:</strong><br>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($tai_xe['SDT']); ?></span><br><br>
                                            <strong>Tuổi:</strong><br>
                                            <span class="badge bg-secondary">
                                            <?php echo date_diff(date_create($tai_xe['NgaySinh']), date_create())->y; ?> tuổi
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <a href="view.php?id=<?php echo $tai_xe['MaTaiXe']; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Xem chi tiết
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Cập nhật
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
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
