<?php
$page_title = "Quản lý bảng giá";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $tuyen = $_GET['delete_tuyen'];
    $loai = $_GET['delete_loai'];
    $ngay = $_GET['delete_ngay'];

    try {
        $query = "DELETE FROM bang_gia WHERE MaTuyenDuong = ? AND MaLoaiXe = ? AND NgayBatDau = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$tuyen, $loai, $ngay]);
        $success = "Xóa bảng giá thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách bảng giá
try {
    $query = "SELECT bg.*, td.DiemDau, td.DiemCuoi, lx.TenLoaiXe, lx.SoGhe,
                     CASE 
                         WHEN bg.NgayBatDau > CURDATE() THEN 'Chưa áp dụng'
                         WHEN bg.NgayKetThuc IS NOT NULL AND bg.NgayKetThuc < CURDATE() THEN 'Đã hết hạn'
                         ELSE 'Đang áp dụng'
                     END as TrangThai
              FROM bang_gia bg
              LEFT JOIN tuyen_duong td ON bg.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN loai_xe lx ON bg.MaLoaiXe = lx.MaLoaiXe
              ORDER BY bg.NgayBatDau DESC, bg.MaTuyenDuong, bg.MaLoaiXe";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $bang_gia_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-tags text-primary me-2"></i>Quản lý bảng giá</h2>
                <p class="text-muted mb-0">Quản lý giá vé theo tuyến đường và loại xe</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Thêm bảng giá
            </a>
        </div>

        <!-- Thông báo -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách bảng giá</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($bang_gia_list); ?> bảng giá</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($bang_gia_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có bảng giá nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Thêm bảng giá đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="20%">Tuyến đường</th>
                                <th width="15%">Loại xe</th>
                                <th width="12%">Giá vé</th>
                                <th width="18%">Thời gian áp dụng</th>
                                <th width="12%">Trạng thái</th>
                                <th width="10%">Giá/km</th>
                                <th width="13%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($bang_gia_list as $gia): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($gia['DiemDau']); ?></div>
                                        <div class="text-primary">
                                            <i class="fas fa-arrow-down me-1"></i>
                                        </div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($gia['DiemCuoi']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($gia['MaTuyenDuong']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($gia['TenLoaiXe']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($gia['MaLoaiXe']); ?> - <?php echo $gia['SoGhe']; ?> ghế
                                        </small>
                                    </td>
                                    <td>
                                    <span class="fw-bold text-success fs-6">
                                        <?php echo number_format($gia['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                    </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong>Từ:</strong> <?php echo date('d/m/Y', strtotime($gia['NgayBatDau'])); ?><br>
                                            <strong>Đến:</strong>
                                            <?php echo $gia['NgayKetThuc'] ? date('d/m/Y', strtotime($gia['NgayKetThuc'])) : '<span class="text-info">Không giới hạn</span>'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        switch($gia['TrangThai']) {
                                            case 'Đang áp dụng': $badge_class = 'bg-success'; break;
                                            case 'Chưa áp dụng': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Đã hết hạn': $badge_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $gia['TrangThai']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <?php
                                        // Tính giá per km (lấy từ tuyến đường)
                                        $query_distance = "SELECT DoDai FROM tuyen_duong WHERE MaTuyenDuong = ?";
                                        $stmt_distance = $db->prepare($query_distance);
                                        $stmt_distance->execute([$gia['MaTuyenDuong']]);
                                        $distance = $stmt_distance->fetchColumn();

                                        if ($distance > 0) {
                                            $gia_per_km = $gia['GiaVeNiemYet'] / $distance;
                                            echo '<small class="text-muted">' . number_format($gia_per_km, 0, ',', '.') . 'đ/km</small>';
                                        } else {
                                            echo '<small class="text-muted">N/A</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?tuyen=<?php echo $gia['MaTuyenDuong']; ?>&loai=<?php echo $gia['MaLoaiXe']; ?>&ngay=<?php echo $gia['NgayBatDau']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($gia['TrangThai'] != 'Đã hết hạn'): ?>
                                                <a href="edit.php?tuyen=<?php echo $gia['MaTuyenDuong']; ?>&loai=<?php echo $gia['MaLoaiXe']; ?>&ngay=<?php echo $gia['NgayBatDau']; ?>"
                                                   class="btn btn-sm btn-outline-warning"
                                                   data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?delete=1&delete_tuyen=<?php echo $gia['MaTuyenDuong']; ?>&delete_loai=<?php echo $gia['MaLoaiXe']; ?>&delete_ngay=<?php echo $gia['NgayBatDau']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa bảng giá này?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Đã hết hạn, không thể sửa">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
