<?php
$page_title = "Thêm tuyến đường mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

if ($_POST) {
    $ma_tuyen_duong = trim(strtoupper($_POST['ma_tuyen_duong']));
    $diem_dau = trim($_POST['diem_dau']);
    $diem_cuoi = trim($_POST['diem_cuoi']);
    $do_phuc_tap = intval($_POST['do_phuc_tap']);
    $do_dai = floatval($_POST['do_dai']);

    // Validation
    if (empty($ma_tuyen_duong) || empty($diem_dau) || empty($diem_cuoi)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } elseif ($do_dai <= 0) {
        $error = "Độ dài tuyến đường phải lớn hơn 0!";
    } elseif ($do_dai > 5000) {
        $error = "Độ dài tuyến đường không được vượt quá 5000km!";
    } elseif ($do_phuc_tap < 1 || $do_phuc_tap > 3) {
        $error = "Độ phức tạp không hợp lệ!";
    } elseif (strtolower($diem_dau) === strtolower($diem_cuoi)) {
        $error = "Điểm đầu và điểm cuối không được giống nhau!";
    } else {
        try {
            // Kiểm tra trùng mã tuyến
            $check_query = "SELECT COUNT(*) FROM tuyen_duong WHERE MaTuyenDuong = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$ma_tuyen_duong]);

            if ($check_stmt->fetchColumn() > 0) {
                $error = "Mã tuyến đường đã tồn tại!";
            } else {
                $query = "INSERT INTO tuyen_duong (MaTuyenDuong, DiemDau, DiemCuoi, DoPhucTap, DoDai) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);

                if ($stmt->execute([$ma_tuyen_duong, $diem_dau, $diem_cuoi, $do_phuc_tap, $do_dai])) {
                    $success = "Thêm tuyến đường thành công!";
                    $_POST = array();
                } else {
                    $error = "Có lỗi xảy ra khi thêm tuyến đường!";
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
    <div class="col-md-10 col-lg-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Thêm tuyến đường mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Tuyến đường</a></li>
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-tag me-1"></i>Mã tuyến đường <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ma_tuyen_duong"
                                   value="<?php echo isset($_POST['ma_tuyen_duong']) ? htmlspecialchars($_POST['ma_tuyen_duong']) : ''; ?>"
                                   placeholder="VD: T001, HN-SG"
                                   style="text-transform: uppercase;"
                                   maxlength="4"
                                   required>
                            <div class="form-text">Mã tối đa 4 ký tự</div>
                            <div class="invalid-feedback">Vui lòng nhập mã tuyến đường</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-ruler me-1"></i>Độ dài (km) <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control"
                                   name="do_dai"
                                   value="<?php echo isset($_POST['do_dai']) ? $_POST['do_dai'] : ''; ?>"
                                   min="0.1"
                                   max="5000"
                                   step="0.1"
                                   placeholder="VD: 150.5"
                                   required>
                            <div class="form-text">Từ 0.1 đến 5000 km</div>
                            <div class="invalid-feedback">Vui lòng nhập độ dài hợp lệ</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-level-up-alt me-1"></i>Độ phức tạp <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="do_phuc_tap" required>
                                <option value="">-- Chọn độ phức tạp --</option>
                                <option value="1" <?php echo (isset($_POST['do_phuc_tap']) && $_POST['do_phuc_tap'] == '1') ? 'selected' : ''; ?>>
                                    Đơn giản (đường thẳng, ít dốc)
                                </option>
                                <option value="2" <?php echo (isset($_POST['do_phuc_tap']) && $_POST['do_phuc_tap'] == '2') ? 'selected' : ''; ?>>
                                    Trung bình (có khúc cua, dốc nhẹ)
                                </option>
                                <option value="3" <?php echo (isset($_POST['do_phuc_tap']) && $_POST['do_phuc_tap'] == '3') ? 'selected' : ''; ?>>
                                    Phức tạp (nhiều khúc cua, dốc cao)
                                </option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn độ phức tạp</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-1 text-success"></i>Điểm đầu <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="diem_dau"
                                   value="<?php echo isset($_POST['diem_dau']) ? htmlspecialchars($_POST['diem_dau']) : ''; ?>"
                                   placeholder="VD: Hà Nội, TP.HCM, Đà Nẵng"
                                   maxlength="100"
                                   required>
                            <div class="invalid-feedback">Vui lòng nhập điểm đầu</div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-1 text-danger"></i>Điểm cuối <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="diem_cuoi"
                                   value="<?php echo isset($_POST['diem_cuoi']) ? htmlspecialchars($_POST['diem_cuoi']) : ''; ?>"
                                   placeholder="VD: Hà Nội, TP.HCM, Đà Nẵng"
                                   maxlength="100"
                                   required>
                            <div class="invalid-feedback">Vui lòng nhập điểm cuối</div>
                        </div>
                    </div>

                    <!-- Gợi ý tuyến phổ biến -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-lightbulb me-2"></i>Gợi ý tuyến đường phổ biến
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Tuyến Bắc Nam:</strong><br>
                                            - Hà Nội → TP.HCM (1726km)<br>
                                            - Hà Nội → Đà Nẵng (791km)<br>
                                            - Đà Nẵng → TP.HCM (964km)
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Tuyến nội vùng:</strong><br>
                                            - Hà Nội → Hải Phòng (102km)<br>
                                            - TP.HCM → Vũng Tàu (125km)<br>
                                            - TP.HCM → Cần Thơ (169km)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hướng dẫn độ phức tạp -->
                    <div class="mb-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn đánh giá độ phức tạp</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <span class="badge bg-success mb-2">Đơn giản</span>
                                            <p class="small text-muted">Đường cao tốc, quốc lộ thẳng, ít dốc và khúc cua</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <span class="badge bg-warning text-dark mb-2">Trung bình</span>
                                            <p class="small text-muted">Đường tỉnh lộ, có một số dốc và khúc cua vừa phải</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <span class="badge bg-danger mb-2">Phức tạp</span>
                                            <p class="small text-muted">Đường miền núi, nhiều dốc cao, khúc cua nguy hiểm</p>
                                        </div>
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
                            <i class="fas fa-save me-2"></i>Lưu tuyến đường
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
