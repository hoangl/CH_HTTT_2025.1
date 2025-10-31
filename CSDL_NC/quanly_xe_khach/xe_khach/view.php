<?php
$page_title = "Chi tiết xe khách";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin xe khách chi tiết
try {
    $query = "SELECT xk.*, lx.TenLoaiXe, lx.SoGhe
              FROM xe_khach xk 
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe 
              WHERE xk.MaXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $xe_khach = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$xe_khach) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách chuyến xe của xe này
    $query_chuyen = "SELECT cx.*, td.DiemDau, td.DiemCuoi, 
                            COUNT(vx.MaVe) as SoVeDaBan,
                            (lx.SoGhe - 2) as SoGheToiDa,
                            CASE cx.TrangThai 
                                WHEN 1 THEN 'Chờ khởi hành'
                                WHEN 2 THEN 'Hoàn thành'
                                ELSE 'Không xác định'
                            END as TenTrangThai
                     FROM chuyen_xe cx
                     LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
                     LEFT JOIN loai_xe lx ON ? = (SELECT MaLoaiXe FROM xe_khach WHERE MaXe = cx.MaXe)
                     LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
                     WHERE cx.MaXe = ?
                     GROUP BY cx.MaChuyenXe
                     ORDER BY cx.GioDi DESC
                     LIMIT 10";
    $stmt_chuyen = $db->prepare($query_chuyen);
    $stmt_chuyen->execute([$xe_khach['MaLoaiXe'], $id]);
    $danh_sach_chuyen = $stmt_chuyen->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê
    $query_stats = "SELECT 
                        COUNT(cx.MaChuyenXe) as TongChuyenXe,
                        COUNT(CASE WHEN cx.TrangThai = 1 THEN 1 END) as ChuyenDangChay,
                        COUNT(CASE WHEN cx.TrangThai = 2 THEN 1 END) as ChuyenHoanThanh,
                        COALESCE(SUM(CASE WHEN vx.MaVe IS NOT NULL THEN 1 END), 0) as TongVeBan
                    FROM chuyen_xe cx
                    LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
                    WHERE cx.MaXe = ?";
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->execute([$id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết xe khách</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Xe khách</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($xe_khach['MaXe']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="edit.php?id=<?php echo $xe_khach['MaXe']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa
                </a>
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
            <!-- Thông tin xe -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bus text-primary me-2"></i>Thông tin xe
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-bus fa-3x"></i>
                            </div>
                        </div>

                        <h4 class="text-primary mb-3"><?php echo htmlspecialchars($xe_khach['MaXe']); ?></h4>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Loại xe:</td>
                                <td><?php echo htmlspecialchars($xe_khach['TenLoaiXe']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Mã loại:</td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($xe_khach['MaLoaiXe']); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Số ghế:</td>
                                <td><span class="badge bg-secondary"><?php echo $xe_khach['SoGhe']; ?> ghế</span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Tình trạng bảo dưỡng -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-tools text-warning me-2"></i>Tình trạng bảo dưỡng
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $ngay_con_lai = $xe_khach['SoNgayBDConLai'];
                        $han_dk = strtotime($xe_khach['HanDangKiem']);
                        $now = time();
                        ?>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Bảo dưỡng còn:</label>
                            <div>
                                <?php
                                if ($ngay_con_lai <= 0) {
                                    echo '<span class="badge bg-danger fs-6">Cần bảo dưỡng ngay!</span>';
                                } elseif ($ngay_con_lai <= 10) {
                                    echo '<span class="badge bg-warning text-dark fs-6">' . $ngay_con_lai . ' ngày</span>';
                                } else {
                                    echo '<span class="badge bg-success fs-6">' . $ngay_con_lai . ' ngày</span>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Hạn đăng kiểm:</label>
                            <div>
                                <?php
                                if ($han_dk <= $now) {
                                    echo '<span class="badge bg-danger fs-6">Đã hết hạn</span>';
                                } elseif (($han_dk - $now) <= (30 * 24 * 3600)) {
                                    echo '<span class="badge bg-warning text-dark fs-6">' . date('d/m/Y', $han_dk) . '</span>';
                                } else {
                                    echo '<span class="badge bg-success fs-6">' . date('d/m/Y', $han_dk) . '</span>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="text-center">
                            <?php
                            if ($ngay_con_lai <= 0 || $han_dk <= $now) {
                                echo '<span class="badge bg-danger">Xe cần bảo dưỡng/đăng kiểm</span>';
                            } else {
                                echo '<span class="badge bg-success">Xe hoạt động tốt</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thống kê và lịch sử chuyến -->
            <div class="col-lg-8">
                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-route fa-2x"></i>
                                </div>
                                <h4 class="text-primary"><?php echo $stats['TongChuyenXe']; ?></h4>
                                <small class="text-muted">Tổng chuyến</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h4 class="text-warning"><?php echo $stats['ChuyenDangChay']; ?></h4>
                                <small class="text-muted">Đang chạy</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <h4 class="text-success"><?php echo $stats['ChuyenHoanThanh']; ?></h4>
                                <small class="text-muted">Hoàn thành</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <div class="text-info mb-2">
                                    <i class="fas fa-ticket-alt fa-2x"></i>
                                </div>
                                <h4 class="text-info"><?php echo $stats['TongVeBan']; ?></h4>
                                <small class="text-muted">Vé đã bán</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử chuyến xe -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-history text-info me-2"></i>
                                    Lịch sử chuyến xe (10 chuyến gần nhất)
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../chuyen_xe/create.php?xe=<?php echo $xe_khach['MaXe']; ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i>Tạo chuyến
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($danh_sach_chuyen)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-route fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Xe chưa có chuyến nào</p>
                                <a href="../chuyen_xe/create.php?xe=<?php echo $xe_khach['MaXe']; ?>"
                                   class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Tạo chuyến đầu tiên
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Mã chuyến</th>
                                        <th>Tuyến đường</th>
                                        <th>Thời gian</th>
                                        <th>Vé bán</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($danh_sach_chuyen as $chuyen): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($chuyen['MaChuyenXe']); ?></small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <strong><?php echo htmlspecialchars($chuyen['DiemDau']); ?></strong><br>
                                                    <i class="fas fa-arrow-down text-primary"></i><br>
                                                    <strong><?php echo htmlspecialchars($chuyen['DiemCuoi']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <strong>Đi:</strong> <?php echo date('d/m/Y H:i', strtotime($chuyen['GioDi'])); ?><br>
                                                    <strong>Đến:</strong> <?php echo date('d/m/Y H:i', strtotime($chuyen['GioDen'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $ti_le = $chuyen['SoGheToiDa'] > 0 ? ($chuyen['SoVeDaBan'] / $chuyen['SoGheToiDa']) * 100 : 0;
                                                $badge_class = $ti_le >= 80 ? 'bg-success' : ($ti_le >= 50 ? 'bg-warning' : 'bg-info');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $chuyen['SoVeDaBan']; ?>/<?php echo $chuyen['SoGheToiDa']; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = $chuyen['TrangThai'] == 1 ? 'bg-warning text-dark' : 'bg-success';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $chuyen['TenTrangThai']; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../chuyen_xe/view.php?id=<?php echo $chuyen['MaChuyenXe']; ?>"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($chuyen['TrangThai'] == 1): ?>
                                                        <a href="../chuyen_xe/edit.php?id=<?php echo $chuyen['MaChuyenXe']; ?>"
                                                           class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>
