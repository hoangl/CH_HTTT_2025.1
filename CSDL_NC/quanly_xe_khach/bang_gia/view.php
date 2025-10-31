<?php
$page_title = "Chi tiết bảng giá";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy thông tin từ URL
$tuyen = isset($_GET['tuyen']) ? $_GET['tuyen'] : '';
$loai = isset($_GET['loai']) ? $_GET['loai'] : '';
$ngay = isset($_GET['ngay']) ? $_GET['ngay'] : '';

if (empty($tuyen) || empty($loai) || empty($ngay)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin bảng giá chi tiết
try {
    $query = "SELECT bg.*, td.DiemDau, td.DiemCuoi, td.DoDai, td.DoPhucTap, lx.TenLoaiXe, lx.SoGhe,
                     CASE 
                         WHEN bg.NgayBatDau > CURDATE() THEN 'Chưa áp dụng'
                         WHEN bg.NgayKetThuc IS NOT NULL AND bg.NgayKetThuc < CURDATE() THEN 'Đã hết hạn'
                         ELSE 'Đang áp dụng'
                     END as TrangThai
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

    // Lấy lịch sử giá cùng tuyến và loại xe
    $query_history = "SELECT *, 
                             CASE 
                                 WHEN NgayBatDau > CURDATE() THEN 'Chưa áp dụng'
                                 WHEN NgayKetThuc IS NOT NULL AND NgayKetThuc < CURDATE() THEN 'Đã hết hạn'
                                 ELSE 'Đang áp dụng'
                             END as TrangThai
                      FROM bang_gia 
                      WHERE MaTuyenDuong = ? AND MaLoaiXe = ?
                      ORDER BY NgayBatDau DESC";
    $stmt_history = $db->prepare($query_history);
    $stmt_history->execute([$tuyen, $loai]);
    $lich_su_gia = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

    // Lấy so sánh giá với các loại xe khác cùng tuyến
    $query_compare = "SELECT bg.*, lx.TenLoaiXe, lx.SoGhe
                      FROM bang_gia bg
                      LEFT JOIN loai_xe lx ON bg.MaLoaiXe = lx.MaLoaiXe
                      WHERE bg.MaTuyenDuong = ? AND bg.MaLoaiXe != ?
                      AND (bg.NgayKetThuc IS NULL OR bg.NgayKetThuc >= CURDATE())
                      AND bg.NgayBatDau <= CURDATE()
                      ORDER BY bg.GiaVeNiemYet";
    $stmt_compare = $db->prepare($query_compare);
    $stmt_compare->execute([$tuyen, $loai]);
    $so_sanh_gia = $stmt_compare->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết bảng giá</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Bảng giá</a></li>
                        <li class="breadcrumb-item active">Chi tiết</li>
                    </ol>
                </nav>
            </div>
            <div>
                <?php if ($bang_gia['TrangThai'] != 'Đã hết hạn'): ?>
                    <a href="edit.php?tuyen=<?php echo $bang_gia['MaTuyenDuong']; ?>&loai=<?php echo $bang_gia['MaLoaiXe']; ?>&ngay=<?php echo $bang_gia['NgayBatDau']; ?>" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Thông tin bảng giá -->
            <div class="col-lg-5 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tag text-primary me-2"></i>Thông tin bảng giá
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-dollar-sign fa-3x"></i>
                            </div>
                        </div>

                        <h3 class="text-success mb-3">
                            <?php echo number_format($bang_gia['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                        </h3>

                        <!-- Trạng thái -->
                        <div class="mb-4">
                            <?php
                            $badge_class = '';
                            switch($bang_gia['TrangThai']) {
                                case 'Đang áp dụng': $badge_class = 'bg-success'; break;
                                case 'Chưa áp dụng': $badge_class = 'bg-warning text-dark'; break;
                                case 'Đã hết hạn': $badge_class = 'bg-secondary'; break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?> fs-6">
                                <?php echo $bang_gia['TrangThai']; ?>
                            </span>
                        </div>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Tuyến:</td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($bang_gia['MaTuyenDuong']); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Loại xe:</td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($bang_gia['MaLoaiXe']); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Giá/km:</td>
                                <td>
                                    <span class="text-success fw-bold">
                                        <?php echo number_format($bang_gia['GiaVeNiemYet'] / $bang_gia['DoDai'], 0, ',', '.'); ?>đ
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Chi tiết tuyến và xe -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle text-info me-2"></i>Chi tiết
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Tuyến đường:</strong><br>
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <span class="badge bg-success me-2"><?php echo htmlspecialchars($bang_gia['DiemDau']); ?></span>
                                <i class="fas fa-arrow-right text-primary"></i>
                                <span class="badge bg-danger ms-2"><?php echo htmlspecialchars($bang_gia['DiemCuoi']); ?></span>
                            </div>
                            <small class="text-muted">
                                Khoảng cách: <?php echo number_format($bang_gia['DoDai'], 1); ?> km |
                                Độ phức tạp:
                                <?php
                                $phuc_tap_text = '';
                                switch($bang_gia['DoPhucTap']) {
                                    case 1: $phuc_tap_text = 'Đơn giản'; break;
                                    case 2: $phuc_tap_text = 'Trung bình'; break;
                                    case 3: $phuc_tap_text = 'Phức tạp'; break;
                                }
                                echo $phuc_tap_text;
                                ?>
                            </small>
                        </div>

                        <div class="mb-3">
                            <strong>Loại xe:</strong><br>
                            <span><?php echo htmlspecialchars($bang_gia['TenLoaiXe']); ?></span><br>
                            <small class="text-muted">Số ghế: <?php echo $bang_gia['SoGhe']; ?> ghế</small>
                        </div>

                        <div>
                            <strong>Thời gian áp dụng:</strong><br>
                            <small>
                                <strong>Từ:</strong> <?php echo date('d/m/Y', strtotime($bang_gia['NgayBatDau'])); ?><br>
                                <strong>Đến:</strong> <?php echo $bang_gia['NgayKetThuc'] ? date('d/m/Y', strtotime($bang_gia['NgayKetThuc'])) : 'Không giới hạn'; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lịch sử và so sánh -->
            <div class="col-lg-7">
                <!-- So sánh với loại xe khác -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-balance-scale text-warning me-2"></i>
                            So sánh giá cùng tuyến
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($so_sanh_gia)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Không có loại xe khác để so sánh</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Loại xe</th>
                                        <th>Số ghế</th>
                                        <th>Giá vé</th>
                                        <th>Giá/km</th>
                                        <th>So sánh</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($so_sanh_gia as $gia_khac): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?php echo htmlspecialchars($gia_khac['TenLoaiXe']); ?></span><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($gia_khac['MaLoaiXe']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $gia_khac['SoGhe']; ?></span>
                                            </td>
                                            <td>
                                            <span class="fw-bold text-success">
                                                <?php echo number_format($gia_khac['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                            </span>
                                            </td>
                                            <td>
                                                <small><?php echo number_format($gia_khac['GiaVeNiemYet'] / $bang_gia['DoDai'], 0, ',', '.'); ?>đ</small>
                                            </td>
                                            <td>
                                                <?php
                                                $chenh_lech = (($gia_khac['GiaVeNiemYet'] - $bang_gia['GiaVeNiemYet']) / $bang_gia['GiaVeNiemYet']) * 100;
                                                if ($chenh_lech > 0) {
                                                    echo '<span class="badge bg-danger">+' . number_format($chenh_lech, 1) . '%</span>';
                                                } elseif ($chenh_lech < 0) {
                                                    echo '<span class="badge bg-success">' . number_format($chenh_lech, 1) . '%</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Bằng nhau</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lịch sử giá -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history text-info me-2"></i>
                            Lịch sử giá
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($lich_su_gia) <= 1): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có lịch sử thay đổi giá</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php
                                $previous_price = null;
                                foreach ($lich_su_gia as $index => $gia_cu):
                                    $is_current = ($gia_cu['NgayBatDau'] == $bang_gia['NgayBatDau']);
                                    ?>
                                    <div class="timeline-item <?php echo $is_current ? 'current' : ''; ?>">
                                        <div class="timeline-marker <?php echo $is_current ? 'bg-primary' : 'bg-secondary'; ?>"></div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1 <?php echo $is_current ? 'text-primary' : ''; ?>">
                                                        <?php echo number_format($gia_cu['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                                        <?php if ($is_current): ?>
                                                            <span class="badge bg-primary ms-2">Hiện tại</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        Từ <?php echo date('d/m/Y', strtotime($gia_cu['NgayBatDau'])); ?>
                                                        <?php if ($gia_cu['NgayKetThuc']): ?>
                                                            đến <?php echo date('d/m/Y', strtotime($gia_cu['NgayKetThuc'])); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($previous_price): ?>
                                                        <?php
                                                        $change = (($gia_cu['GiaVeNiemYet'] - $previous_price) / $previous_price) * 100;
                                                        if ($change > 0) {
                                                            echo '<small class="text-success">+' . number_format($change, 1) . '%</small>';
                                                        } elseif ($change < 0) {
                                                            echo '<small class="text-danger">' . number_format($change, 1) . '%</small>';
                                                        }
                                                        ?>
                                                    <?php endif; ?>
                                                    <div>
                                                <span class="badge <?php
                                                switch($gia_cu['TrangThai']) {
                                                    case 'Đang áp dụng': echo 'bg-success'; break;
                                                    case 'Chưa áp dụng': echo 'bg-warning text-dark'; break;
                                                    case 'Đã hết hạn': echo 'bg-secondary'; break;
                                                }
                                                ?> small">
                                                    <?php echo $gia_cu['TrangThai']; ?>
                                                </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $previous_price = $gia_cu['GiaVeNiemYet'];
                                endforeach;
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-marker {
        position: absolute;
        left: -22px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid white;
    }

    .timeline-content {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .timeline-item.current .timeline-content {
        background: #e3f2fd;
        border: 1px solid #2196f3;
    }
</style>

<?php include '../includes/footer.php'; ?>
