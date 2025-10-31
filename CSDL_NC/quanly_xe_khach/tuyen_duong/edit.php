<?php
$page_title = "Chỉnh sửa tuyến đường";
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

// Lấy thông tin tuyến đường hiện tại
try {
    $query = "SELECT * FROM tuyen_duong WHERE MaTuyenDuong = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $tuyen_duong = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tuyen_duong) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $diem_dau = trim($_POST['diem_dau']);
    $diem_cuoi = trim($_POST['diem_cuoi']);
    $do_phuc_tap = intval($_POST['do_phuc_tap']);
    $do_dai = floatval($_POST['do_dai']);

    // Validation
    if (empty($diem_dau) || empty($diem_cuoi)) {
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
            $query = "UPDATE tuyen_duong SET DiemDau = ?, DiemCuoi = ?, DoPhucTap = ?, DoDai = ? WHERE MaTuyenDuong = ?";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$diem_dau, $diem_cuoi, $do_phuc_tap, $do_dai, $id])) {
                $success = "Cập nhật tuyến đường thành công!";
                // Cập nhật lại thông tin
                $tuyen_duong['DiemDau'] = $diem_dau;
                $tuyen_duong['DiemCuoi'] = $diem_cuoi;
                $tuyen_duong['DoPhucTap'] = $do_phuc_tap;
                $tuyen_duong['DoDai'] = $do_dai;
            } else {
                $error = "Có lỗi xảy ra khi cập nhật tuyến đường!";
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
                    <i class="fas fa-edit text-warning me-2"></i>
                    Chỉnh sửa tuyến đường
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Tuyến đường</a></li>
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

                <?php if ($tuyen_duong): ?>
                    <!-- Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tag me-1"></i>Mã tuyến đường
                                </label>
                                <input type="text"
                                       class="form-control bg-light"
                                       value="<?php echo htmlspecialchars($tuyen_duong['MaTuyenDuong']); ?>"
                                       readonly>
                                <div class="form-text text-info">Mã tuyến đường không thể thay đổi</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-ruler me-1"></i>Độ dài (km) <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control"
                                       name="do_dai"
                                       value="<?php echo isset($_POST['do_dai']) ? $_POST['do_dai'] : $tuyen_duong['DoDai']; ?>"
                                       min="0.1"
                                       max="5000"
                                       step="0.1"
                                       required>
                                <div class="invalid-feedback">Vui lòng nhập độ dài hợp lệ</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-level-up-alt me-1"></i>Độ phức tạp <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="do_phuc_tap" required>
                                    <option value="">-- Chọn độ phức tạp --</option>
                                    <option value="1" <?php echo (isset($_POST['do_phuc_tap']) ? ($_POST['do_phuc_tap'] == 1 ? 'selected' : '') : ($tuyen_duong['DoPhucTap'] == 1 ? 'selected' : '')); ?>>
                                        Đơn giản (đường thẳng, ít dốc)
                                    </option>
                                    <option value="2" <?php echo (isset($_POST['do_phuc_tap']) ? ($_POST['do_phuc_tap'] == 2 ? 'selected' : '') : ($tuyen_duong['DoPhucTap'] == 2 ? 'selected' : '')); ?>>
                                        Trung bình (có khúc cua, dốc nhẹ)
                                    </option>
                                    <option value="3" <?php echo (isset($_POST['do_phuc_tap']) ? ($_POST['do_phuc_tap'] == 3 ? 'selected' : '') : ($tuyen_duong['DoPhucTap'] == 3 ? 'selected' : '')); ?>>
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
                                       value="<?php echo isset($_POST['diem_dau']) ? htmlspecialchars($_POST['diem_dau']) : htmlspecialchars($tuyen_duong['DiemDau']); ?>"
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
                                       value="<?php echo isset($_POST['diem_cuoi']) ? htmlspecialchars($_POST['diem_cuoi']) : htmlspecialchars($tuyen_duong['DiemCuoi']); ?>"
                                       maxlength="100"
                                       required>
                                <div class="invalid-feedback">Vui lòng nhập điểm cuối</div>
                            </div>
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
                                            <strong>Mã tuyến:</strong><br>
                                            <span class="text-primary"><?php echo htmlspecialchars($tuyen_duong['MaTuyenDuong']); ?></span><br><br>
                                            <strong>Tuyến hiện tại:</strong><br>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($tuyen_duong['DiemDau']); ?></span>
                                            <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                            <span class="badge bg-danger"><?php echo htmlspecialchars($tuyen_duong['DiemCuoi']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Độ dài hiện tại:</strong><br>
                                            <span class="badge bg-info"><?php echo number_format($tuyen_duong['DoDai'], 1); ?> km</span><br><br>
                                            <strong>Độ phức tạp hiện tại:</strong><br>
                                            <?php
                                            $current_phuc_tap = $tuyen_duong['DoPhucTap'];
                                            $badge_class = '';
                                            $text = '';
                                            switch($current_phuc_tap) {
                                                case 1:
                                                    $badge_class = 'bg-success';
                                                    $text = 'Đơn giản';
                                                    break;
                                                case 2:
                                                    $badge_class = 'bg-warning text-dark';
                                                    $text = 'Trung bình';
                                                    break;
                                                case 3:
                                                    $badge_class = 'bg-danger';
                                                    $text = 'Phức tạp';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $text; ?></span>
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
                            <a href="view.php?id=<?php echo $tuyen_duong['MaTuyenDuong']; ?>" class="btn btn-info">
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

<?php include '../includes/footer.php'; ?>
