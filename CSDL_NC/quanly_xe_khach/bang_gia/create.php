<?php
$page_title = "Thêm bảng giá mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy danh sách tuyến đường và loại xe
try {
    $query_tuyen = "SELECT * FROM tuyen_duong ORDER BY MaTuyenDuong";
    $stmt_tuyen = $db->prepare($query_tuyen);
    $stmt_tuyen->execute();
    $tuyen_duong_list = $stmt_tuyen->fetchAll(PDO::FETCH_ASSOC);

    $query_loai = "SELECT * FROM loai_xe ORDER BY MaLoaiXe";
    $stmt_loai = $db->prepare($query_loai);
    $stmt_loai->execute();
    $loai_xe_list = $stmt_loai->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy dữ liệu: " . $e->getMessage();
}

if ($_POST && !$error) {
    $ma_tuyen_duong = trim($_POST['ma_tuyen_duong']);
    $ma_loai_xe = trim($_POST['ma_loai_xe']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = !empty($_POST['ngay_ket_thuc']) ? $_POST['ngay_ket_thuc'] : null;
    $gia_ve_niem_yet = floatval($_POST['gia_ve_niem_yet']);

    // Validation
    if (empty($ma_tuyen_duong) || empty($ma_loai_xe) || empty($ngay_bat_dau) || $gia_ve_niem_yet <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } elseif ($gia_ve_niem_yet > 10000000) {
        $error = "Giá vé không được vượt quá 10,000,000đ!";
    } elseif (strtotime($ngay_bat_dau) < strtotime(date('Y-m-d'))) {
        $error = "Ngày bắt đầu phải từ hôm nay trở đi!";
    } elseif ($ngay_ket_thuc && strtotime($ngay_ket_thuc) <= strtotime($ngay_bat_dau)) {
        $error = "Ngày kết thúc phải sau ngày bắt đầu!";
    } else {
        try {
            // Kiểm tra trùng lặp
            $check_query = "SELECT COUNT(*) FROM bang_gia 
                           WHERE MaTuyenDuong = ? AND MaLoaiXe = ? AND NgayBatDau = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$ma_tuyen_duong, $ma_loai_xe, $ngay_bat_dau]);

            if ($check_stmt->fetchColumn() > 0) {
                $error = "Đã tồn tại bảng giá cho tuyến đường và loại xe này vào ngày bắt đầu này!";
            } else {
                // Kiểm tra xung đột thời gian
                $conflict_query = "SELECT COUNT(*) FROM bang_gia 
                                  WHERE MaTuyenDuong = ? AND MaLoaiXe = ? 
                                  AND (
                                      (NgayKetThuc IS NULL) OR
                                      (? <= COALESCE(NgayKetThuc, '9999-12-31') AND 
                                       COALESCE(?, '9999-12-31') >= NgayBatDau)
                                  )";
                $conflict_stmt = $db->prepare($conflict_query);
                $conflict_stmt->execute([$ma_tuyen_duong, $ma_loai_xe, $ngay_bat_dau, $ngay_ket_thuc]);

                if ($conflict_stmt->fetchColumn() > 0) {
                    $error = "Khoảng thời gian này bị trung với bảng giá đã tồn tại!";
                } else {
                    $query = "INSERT INTO bang_gia (MaTuyenDuong, MaLoaiXe, NgayBatDau, NgayKetThuc, GiaVeNiemYet) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);

                    if ($stmt->execute([$ma_tuyen_duong, $ma_loai_xe, $ngay_bat_dau, $ngay_ket_thuc, $gia_ve_niem_yet])) {
                        $success = "Thêm bảng giá thành công!";
                        $_POST = array();
                    } else {
                        $error = "Có lỗi xảy ra khi thêm bảng giá!";
                    }
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
                    Thêm bảng giá mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Bảng giá</a></li>
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
                                <i class="fas fa-route me-1"></i>Tuyến đường <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="ma_tuyen_duong" required>
                                <option value="">-- Chọn tuyến đường --</option>
                                <?php foreach ($tuyen_duong_list as $tuyen): ?>
                                    <option value="<?php echo $tuyen['MaTuyenDuong']; ?>"
                                            data-distance="<?php echo $tuyen['DoDai']; ?>"
                                        <?php echo (isset($_POST['ma_tuyen_duong']) && $_POST['ma_tuyen_duong'] == $tuyen['MaTuyenDuong']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tuyen['MaTuyenDuong'] . ' - ' . $tuyen['DiemDau'] . ' → ' . $tuyen['DiemCuoi'] . ' (' . $tuyen['DoDai'] . 'km)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn tuyến đường</div>
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>Giá vé niêm yết <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number"
                                       class="form-control"
                                       name="gia_ve_niem_yet"
                                       id="gia_ve"
                                       value="<?php echo isset($_POST['gia_ve_niem_yet']) ? $_POST['gia_ve_niem_yet'] : ''; ?>"
                                       min="1000"
                                       max="10000000"
                                       step="1000"
                                       placeholder="VD: 150000"
                                       required>
                                <span class="input-group-text">đ</span>
                            </div>
                            <div class="form-text">Từ 1,000đ đến 10,000,000đ</div>
                            <div class="invalid-feedback">Vui lòng nhập giá vé hợp lệ</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Ngày bắt đầu <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   class="form-control"
                                   name="ngay_bat_dau"
                                   value="<?php echo isset($_POST['ngay_bat_dau']) ? $_POST['ngay_bat_dau'] : date('Y-m-d'); ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   required>
                            <div class="invalid-feedback">Vui lòng chọn ngày bắt đầu</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-times me-1"></i>Ngày kết thúc
                            </label>
                            <input type="date"
                                   class="form-control"
                                   name="ngay_ket_thuc"
                                   value="<?php echo isset($_POST['ngay_ket_thuc']) ? $_POST['ngay_ket_thuc'] : ''; ?>">
                            <div class="form-text">Để trống nếu không giới hạn</div>
                        </div>
                    </div>

                    <!-- Thông tin tính toán -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-calculator me-2"></i>Thông tin tham khảo
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div id="route-info">
                                            <small class="text-muted">Chọn tuyến đường để xem thông tin</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="price-info">
                                            <small class="text-muted">Nhập giá vé để tính giá/km</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gợi ý giá -->
                    <div class="mb-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Gợi ý định giá</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Theo loại xe:</strong>
                                        <ul class="mb-0 small">
                                            <li>Xe nhỏ (≤16 ghế): 2,000-3,000đ/km</li>
                                            <li>Xe trung (17-29 ghế): 1,500-2,500đ/km</li>
                                            <li>Xe lớn (≥30 ghế): 1,000-2,000đ/km</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Theo khoảng cách:</strong>
                                        <ul class="mb-0 small">
                                            <li>Ngắn (≤100km): 2,500-4,000đ/km</li>
                                            <li>Trung (101-500km): 1,500-2,500đ/km</li>
                                            <li>Dài (>500km): 1,000-1,800đ/km</li>
                                        </ul>
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
                            <i class="fas fa-save me-2"></i>Lưu bảng giá
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Cập nhật thông tin khi chọn tuyến đường
    document.querySelector('select[name="ma_tuyen_duong"]').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const distance = selectedOption.getAttribute('data-distance');
        const routeInfo = document.getElementById('route-info');

        if (distance) {
            routeInfo.innerHTML = `
            <strong>Khoảng cách:</strong> ${parseFloat(distance).toLocaleString('vi-VN')} km<br>
            <strong>Gợi ý giá:</strong> ${Math.round(distance * 1500).toLocaleString('vi-VN')}đ - ${Math.round(distance * 2500).toLocaleString('vi-VN')}đ
        `;
            calculatePricePerKm();
        } else {
            routeInfo.innerHTML = '<small class="text-muted">Chọn tuyến đường để xem thông tin</small>';
        }
    });

    // Tính giá/km khi nhập giá vé
    document.getElementById('gia_ve').addEventListener('input', function() {
        calculatePricePerKm();
    });

    function calculatePricePerKm() {
        const price = parseFloat(document.getElementById('gia_ve').value);
        const selectedRoute = document.querySelector('select[name="ma_tuyen_duong"]');
        const selectedOption = selectedRoute.options[selectedRoute.selectedIndex];
        const distance = parseFloat(selectedOption.getAttribute('data-distance'));
        const priceInfo = document.getElementById('price-info');

        if (price && distance) {
            const pricePerKm = Math.round(price / distance);
            let category = '';

            if (pricePerKm >= 2500) {
                category = '<span class="badge bg-success">Cao</span>';
            } else if (pricePerKm >= 1500) {
                category = '<span class="badge bg-warning text-dark">Trung bình</span>';
            } else {
                category = '<span class="badge bg-info">Thấp</span>';
            }

            priceInfo.innerHTML = `
            <strong>Giá/km:</strong> ${pricePerKm.toLocaleString('vi-VN')}đ/km ${category}
        `;
        } else {
            priceInfo.innerHTML = '<small class="text-muted">Nhập giá vé để tính giá/km</small>';
        }
    }

    // Validation ngày kết thúc
    document.querySelector('input[name="ngay_bat_dau"]').addEventListener('change', function() {
        const endDate = document.querySelector('input[name="ngay_ket_thuc"]');
        endDate.min = this.value;

        if (endDate.value && endDate.value <= this.value) {
            endDate.value = '';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
