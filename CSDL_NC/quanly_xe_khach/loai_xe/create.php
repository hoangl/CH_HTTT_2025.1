<?php
$page_title = "Thêm loại xe mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

if ($_POST) {
    $ma_loai_xe = trim(strtoupper($_POST['ma_loai_xe']));
    $ten_loai_xe = trim($_POST['ten_loai_xe']);
    $so_ghe = intval($_POST['so_ghe']);

    // Validation
    if (empty($ma_loai_xe) || empty($ten_loai_xe) || $so_ghe <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin hợp lệ!";
    } elseif ($so_ghe > 60) {
        $error = "Số ghế không được vượt quá 60!";
    } else {
        try {
            // Kiểm tra trùng mã
            $check_query = "SELECT COUNT(*) FROM loai_xe WHERE MaLoaiXe = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$ma_loai_xe]);

            if ($check_stmt->fetchColumn() > 0) {
                $error = "Mã loại xe đã tồn tại!";
            } else {
                $query = "INSERT INTO loai_xe (MaLoaiXe, TenLoaiXe, SoGhe) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);

                if ($stmt->execute([$ma_loai_xe, $ten_loai_xe, $so_ghe])) {
                    $success = "Thêm loại xe thành công!";
                    // Reset form
                    $_POST = array();
                } else {
                    $error = "Có lỗi xảy ra khi thêm loại xe!";
                }
            }
        } catch(PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
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
                    Thêm loại xe mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Loại xe</a></li>
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
                                <i class="fas fa-tag me-1"></i>Mã loại xe <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ma_loai_xe"
                                   value="<?php echo isset($_POST['ma_loai_xe']) ? htmlspecialchars($_POST['ma_loai_xe']) : ''; ?>"
                                   placeholder="VD: GN12, LX45..."
                                   style="text-transform: uppercase;"
                                   maxlength="10"
                                   required>
                            <div class="form-text">Mã loại xe sẽ được chuyển thành chữ hoa</div>
                            <div class="invalid-feedback">Vui lòng nhập mã loại xe</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-chair me-1"></i>Số ghế <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control"
                                   name="so_ghe"
                                   value="<?php echo isset($_POST['so_ghe']) ? $_POST['so_ghe'] : ''; ?>"
                                   min="1"
                                   max="60"
                                   placeholder="Nhập số ghế..."
                                   required>
                            <div class="form-text">Từ 1 đến 60 ghế</div>
                            <div class="invalid-feedback">Vui lòng nhập số ghế hợp lệ (1-60)</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-signature me-1"></i>Tên loại xe <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               name="ten_loai_xe"
                               value="<?php echo isset($_POST['ten_loai_xe']) ? htmlspecialchars($_POST['ten_loai_xe']) : ''; ?>"
                               placeholder="VD: Giường nằm 12 chỗ, Limousine 9 chỗ..."
                               maxlength="100"
                               required>
                        <div class="form-text">Tên mô tả cho loại xe</div>
                        <div class="invalid-feedback">Vui lòng nhập tên loại xe</div>
                    </div>

                    <!-- Gợi ý loại xe phổ biến -->
                    <div class="mb-4">
                        <label class="form-label text-info">
                            <i class="fas fa-lightbulb me-1"></i>Gợi ý loại xe phổ biến:
                        </label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="fw-bold">Xe nhỏ (≤16 ghế)</small><br>
                                        <small class="text-muted">GN12, LM09, LM11</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="fw-bold">Xe trung (17-29 ghế)</small><br>
                                        <small class="text-muted">GN24, LX24, LX28</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="fw-bold">Xe lớn (≥30 ghế)</small><br>
                                        <small class="text-muted">LX30, LX45, GN40</small>
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
                            <i class="fas fa-save me-2"></i>Lưu loại xe
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div class="card mt-4 border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li><strong>Mã loại xe:</strong> Mã định danh duy nhất, không trùng lặp</li>
                    <li><strong>Tên loại xe:</strong> Mô tả chi tiết về loại xe</li>
                    <li><strong>Số ghế:</strong> Tổng số ghế của loại xe (không bao gồm tài xế)</li>
                    <li>Các trường có dấu <span class="text-danger">*</span> là bắt buộc</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
