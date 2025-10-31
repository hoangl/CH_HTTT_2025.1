<?php
$page_title = "Thêm xe khách mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy danh sách loại xe
try {
    $query_loai = "SELECT * FROM loai_xe ORDER BY MaLoaiXe";
    $stmt_loai = $db->prepare($query_loai);
    $stmt_loai->execute();
    $loai_xe_list = $stmt_loai->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy danh sách loại xe: " . $e->getMessage();
}

if ($_POST && !$error) {
    $ma_xe = trim(strtoupper($_POST['ma_xe']));
    $ma_loai_xe = trim($_POST['ma_loai_xe']);
    $so_ngay_bd = intval($_POST['so_ngay_bd']);
    $han_dang_kiem = $_POST['han_dang_kiem'];

    // Validation
    if (empty($ma_xe) || empty($ma_loai_xe) || empty($han_dang_kiem)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif ($so_ngay_bd < 0) {
        $error = "Số ngày bảo dưỡng còn lại không được âm!";
    } elseif (strtotime($han_dang_kiem) <= time()) {
        $error = "Hạn đăng kiểm phải sau ngày hiện tại!";
    } else {
        // Kiểm tra biển số xe hợp lệ
        if (!preg_match('/^[0-9]{2}[A-Z]{1,2}-[0-9]{4,5}$/', $ma_xe)) {
            $error = "Biển số xe không hợp lệ! (VD: 29A-12345, 30AB-12345)";
        } else {
            try {
                // Kiểm tra trùng biển số
                $check_query = "SELECT COUNT(*) FROM xe_khach WHERE MaXe = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$ma_xe]);

                if ($check_stmt->fetchColumn() > 0) {
                    $error = "Biển số xe đã tồn tại!";
                } else {
                    $query = "INSERT INTO xe_khach (MaXe, MaLoaiXe, SoNgayBDConLai, HanDangKiem) VALUES (?, ?, ?, ?)";
                    $stmt = $db->prepare($query);

                    if ($stmt->execute([$ma_xe, $ma_loai_xe, $so_ngay_bd, $han_dang_kiem])) {
                        $success = "Thêm xe khách thành công!";
                        $_POST = array();
                    } else {
                        $error = "Có lỗi xảy ra khi thêm xe khách!";
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
                    Thêm xe khách mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Xe khách</a></li>
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
                                <i class="fas fa-id-card me-1"></i>Biển số xe <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ma_xe"
                                   value="<?php echo isset($_POST['ma_xe']) ? htmlspecialchars($_POST['ma_xe']) : ''; ?>"
                                   placeholder="VD: 29A-12345"
                                   style="text-transform: uppercase;"
                                   pattern="^[0-9]{2}[A-Z]{1,2}-[0-9]{4,5}$"
                                   maxlength="10"
                                   required>
                            <div class="form-text">Định dạng: XXX-XXXXX (X = số/chữ)</div>
                            <div class="invalid-feedback">Vui lòng nhập biển số xe hợp lệ</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-car me-1"></i>Loại xe <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="ma_loai_xe" required>
                                <option value="">-- Chọn loại xe --</option>
                                <?php foreach ($loai_xe_list as $loai): ?>
                                    <option value="<?php echo $loai['MaLoaiXe']; ?>"
                                        <?php echo (isset($_POST['ma_loai_xe']) && $_POST['ma_loai_xe'] == $loai['MaLoaiXe']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loai['MaLoaiXe'] . ' - ' . $loai['TenLoaiXe'] . ' (' . $loai['SoGhe'] . ' ghế)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn loại xe</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-tools me-1"></i>Số ngày bảo dưỡng còn lại
                            </label>
                            <input type="number"
                                   class="form-control"
                                   name="so_ngay_bd"
                                   value="<?php echo isset($_POST['so_ngay_bd']) ? $_POST['so_ngay_bd'] : '90'; ?>"
                                   min="0"
                                   max="365">
                            <div class="form-text">Mặc định 90 ngày</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-certificate me-1"></i>Hạn đăng kiểm <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   class="form-control"
                                   name="han_dang_kiem"
                                   value="<?php echo isset($_POST['han_dang_kiem']) ? $_POST['han_dang_kiem'] : ''; ?>"
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   required>
                            <div class="invalid-feedback">Vui lòng chọn hạn đăng kiểm</div>
                        </div>
                    </div>

                    <!-- Hướng dẫn biển số -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body py-3">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-info-circle me-2"></i>Hướng dẫn biển số xe
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Biển trắng:</strong> 29A-12345<br>
                                            <strong>Biển xanh:</strong> 30A-00123<br>
                                            <strong>Biển vàng:</strong> 51B-67890
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            - 2 số đầu: Mã tỉnh/thành<br>
                                            - 1-2 chữ cái: Loại phương tiện<br>
                                            - 4-5 số cuối: Số thứ tự
                                        </small>
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
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Làm mới
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Lưu xe khách
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
