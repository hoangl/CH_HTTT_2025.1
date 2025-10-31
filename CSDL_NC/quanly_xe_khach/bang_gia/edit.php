<?php
$page_title = "Chỉnh sửa bảng giá";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy thông tin từ URL
$tuyen = isset($_GET['tuyen']) ? $_GET['tuyen'] : '';
$loai = isset($_GET['loai']) ? $_GET['loai'] : '';
$ngay = isset($_GET['ngay']) ? $_GET['ngay'] : '';

if (empty($tuyen) || empty($loai) || empty($ngay)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin bảng giá hiện tại
try {
    $query = "SELECT bg.*, td.DiemDau, td.DiemCuoi, td.DoDai, lx.TenLoaiXe, lx.SoGhe
              FROM bang_gia bg
              LEFT JOIN tuyen_duong td ON bg.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN loai_xe lx ON bg.MaLoaiXe = lx.MaLoaiXe
              WHERE bg.MaTuyenDuong = ? AND bg.MaLoaiXe = ? AND bg.NgayBatDau = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$tuyen, $loai, $ngay]);
    $bang_gia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bang_gia) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $ngay_ket_thuc = !empty($_POST['ngay_ket_thuc']) ? $_POST['ngay_ket_thuc'] : null;
    $gia_ve_niem_yet = floatval($_POST['gia_ve_niem_yet']);

    // Validation
    if ($gia_ve_niem_yet <= 0) {
        $error = "Giá vé phải lớn hơn 0!";
    } elseif ($gia_ve_niem_yet > 10000000) {
        $error = "Giá vé không được vượt quá 10,000,000đ!";
    } elseif ($ngay_ket_thuc && strtotime($ngay_ket_thuc) <= strtotime($bang_gia['NgayBatDau'])) {
        $error = "Ngày kết thúc phải sau ngày bắt đầu!";
    } else {
        try {
            $query = "UPDATE bang_gia SET NgayKetThuc = ?, GiaVeNiemYet = ? 
                     WHERE MaTuyenDuong = ? AND MaLoaiXe = ? AND NgayBatDau = ?";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$ngay_ket_thuc, $gia_ve_niem_yet, $tuyen, $loai, $ngay])) {
                $success = "Cập nhật bảng giá thành công!";
                $bang_gia['NgayKetThuc'] = $ngay_ket_thuc;
                $bang_gia['GiaVeNiemYet'] = $gia_ve_niem_yet;
            } else {
                $error = "Có lỗi xảy ra khi cập nhật bảng giá!";
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
                    Chỉnh sửa bảng giá
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Bảng giá</a></li>
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

                <?php if ($bang_gia): ?>
                    <!-- Thông tin không thay đổi -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle text-info me-2"></i>Thông tin cố định
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Tuyến đường:</strong><br>
                                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($bang_gia['MaTuyenDuong']); ?></span><br>
                                        <span class="fw-bold"><?php echo htmlspecialchars($bang_gia['DiemDau']); ?></span>
                                        <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                        <span class="fw-bold"><?php echo htmlspecialchars($bang_gia['DiemCuoi']); ?></span><br>
                                        <small class="text-muted">Khoảng cách: <?php echo number_format($bang_gia['DoDai'], 1); ?> km</small>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Loại xe:</strong><br>
                                        <span class="badge bg-info me-2"><?php echo htmlspecialchars($bang_gia['MaLoaiXe']); ?></span><br>
                                        <span class="fw-bold"><?php echo htmlspecialchars($bang_gia['TenLoaiXe']); ?></span><br>
                                        <small class="text-muted">Số ghế: <?php echo $bang_gia['SoGhe']; ?> ghế</small>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <strong>Ngày bắt đầu:</strong><br>
                                        <span class="badge bg-success"><?php echo date('d/m/Y', strtotime($bang_gia['NgayBatDau'])); ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Giá hiện tại:</strong><br>
                                        <span class="fw-bold text-success fs-5">
                                        <?php echo number_format($bang_gia['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                    </span>
                                        <small class="text-muted d-block">
                                            (~<?php echo number_format($bang_gia['GiaVeNiemYet'] / $bang_gia['DoDai'], 0, ',', '.'); ?>đ/km)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form chỉnh sửa -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Giá vé niêm yết <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="gia_ve_niem_yet"
                                           id="gia_ve"
                                           value="<?php echo isset($_POST['gia_ve_niem_yet']) ? $_POST['gia_ve_niem_yet'] : $bang_gia['GiaVeNiemYet']; ?>"
                                           min="1000"
                                           max="10000000"
                                           step="1000"
                                           required>
                                    <span class="input-group-text">đ</span>
                                </div>
                                <div class="form-text">Từ 1,000đ đến 10,000,000đ</div>
                                <div class="invalid-feedback">Vui lòng nhập giá vé hợp lệ</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar-times me-1"></i>Ngày kết thúc
                                </label>
                                <input type="date"
                                       class="form-control"
                                       name="ngay_ket_thuc"
                                       value="<?php echo isset($_POST['ngay_ket_thuc']) ? $_POST['ngay_ket_thuc'] : $bang_gia['NgayKetThuc']; ?>"
                                       min="<?php echo $bang_gia['NgayBatDau']; ?>">
                                <div class="form-text">Để trống nếu không giới hạn</div>
                            </div>
                        </div>

                        <!-- Thông tin tính toán -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-success">
                                        <i class="fas fa-calculator me-2"></i>Thông tin giá mới
                                    </h6>
                                    <div id="price-info">
                                        <!-- Sẽ được cập nhật bằng JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- So sánh giá -->
                        <div class="mb-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>So sánh giá</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Giá cũ:</strong><br>
                                            <span class="text-muted fs-5"><?php echo number_format($bang_gia['GiaVeNiemYet'], 0, ',', '.'); ?>đ</span><br>
                                            <small class="text-muted">(<?php echo number_format($bang_gia['GiaVeNiemYet'] / $bang_gia['DoDai'], 0, ',', '.'); ?>đ/km)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Giá mới:</strong><br>
                                            <span id="new-price" class="text-success fs-5">-</span><br>
                                            <small id="new-price-per-km" class="text-muted">-</small>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <strong>Thay đổi:</strong>
                                            <span id="price-change" class="ms-2">-</span>
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
                            <a href="view.php?tuyen=<?php echo $bang_gia['MaTuyenDuong']; ?>&loai=<?php echo $bang_gia['MaLoaiXe']; ?>&ngay=<?php echo $bang_gia['NgayBatDau']; ?>" class="btn btn-info">
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
    const originalPrice = <?php echo $bang_gia['GiaVeNiemYet']; ?>;
    const distance = <?php echo $bang_gia['DoDai']; ?>;

    // Cập nhật thông tin khi thay đổi giá
    document.getElementById('gia_ve').addEventListener('input', function() {
        const newPrice = parseFloat(this.value);

        if (newPrice) {
            const newPricePerKm = Math.round(newPrice / distance);
            const priceChange = newPrice - originalPrice;
            const percentChange = ((newPrice - originalPrice) / originalPrice * 100).toFixed(1);

            // Cập nhật giá mới
            document.getElementById('new-price').textContent = newPrice.toLocaleString('vi-VN') + 'đ';
            document.getElementById('new-price-per-km').textContent = '(' + newPricePerKm.toLocaleString('vi-VN') + 'đ/km)';

            // Cập nhật thay đổi
            const changeElement = document.getElementById('price-change');
            const changeText = (priceChange >= 0 ? '+' : '') + priceChange.toLocaleString('vi-VN') + 'đ (' + (percentChange >= 0 ? '+' : '') + percentChange + '%)';

            if (priceChange > 0) {
                changeElement.innerHTML = '<span class="badge bg-success">' + changeText + '</span>';
            } else if (priceChange < 0) {
                changeElement.innerHTML = '<span class="badge bg-danger">' + changeText + '</span>';
            } else {
                changeElement.innerHTML = '<span class="badge bg-secondary">Không thay đổi</span>';
            }

            // Cập nhật thông tin giá
            let category = '';
            if (newPricePerKm >= 2500) {
                category = '<span class="badge bg-success">Cao</span>';
            } else if (newPricePerKm >= 1500) {
                category = '<span class="badge bg-warning text-dark">Trung bình</span>';
            } else {
                category = '<span class="badge bg-info">Thấp</span>';
            }

            document.getElementById('price-info').innerHTML = `
            <strong>Giá/km:</strong> ${newPricePerKm.toLocaleString('vi-VN')}đ/km ${category}
        `;
        } else {
            document.getElementById('new-price').textContent = '-';
            document.getElementById('new-price-per-km').textContent = '-';
            document.getElementById('price-change').textContent = '-';
            document.getElementById('price-info').innerHTML = '<small class="text-muted">Nhập giá vé để xem thông tin</small>';
        }
    });

    // Trigger initial calculation
    document.getElementById('gia_ve').dispatchEvent(new Event('input'));
</script>

<?php include '../includes/footer.php'; ?>
