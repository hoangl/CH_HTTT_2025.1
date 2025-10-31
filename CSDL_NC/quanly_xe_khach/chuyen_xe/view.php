<?php
$page_title = "Chi tiết chuyến xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin chuyến xe chi tiết
try {
    $query = "SELECT cx.*, 
                     td.DiemDau, td.DiemCuoi, td.DoDai, td.DoPhucTap,
                     xk.MaXe, lx.TenLoaiXe, lx.SoGhe,
                     CASE cx.TrangThai 
                         WHEN 1 THEN 'Chờ khởi hành'
                         WHEN 2 THEN 'Hoàn thành'
                         ELSE 'Không xác định'
                     END as TenTrangThai
              FROM chuyen_xe cx
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              WHERE cx.MaChuyenXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $chuyen_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chuyen_xe) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách vé đã bán
    $query_ve = "SELECT * FROM ve_xe WHERE MaChuyenXe = ? ORDER BY ViTri";
    $stmt_ve = $db->prepare($query_ve);
    $stmt_ve->execute([$id]);
    $danh_sach_ve = $stmt_ve->fetchAll(PDO::FETCH_ASSOC);

    // Lấy phân công tài xế
    $query_phan_cong = "SELECT pc.*, tx.HoTen, tx.SDT,
                               CASE pc.VaiTro
                                   WHEN 1 THEN 'Tài xế chính'
                                   WHEN 2 THEN 'Lái phụ'
                                   ELSE 'Không xác định'
                               END as TenVaiTro
                        FROM phan_cong pc
                        LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
                        WHERE pc.MaChuyenXe = ?
                        ORDER BY pc.VaiTro";
    $stmt_phan_cong = $db->prepare($query_phan_cong);
    $stmt_phan_cong->execute([$id]);
    $phan_cong = $stmt_phan_cong->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê
    $so_ghe_toi_da = $chuyen_xe['SoGhe'] - 2; // Trừ ghế tài xế
    $so_ve_ban = count($danh_sach_ve);
    $so_ghe_trong = $so_ghe_toi_da - $so_ve_ban;
    $ti_le_ban = $so_ghe_toi_da > 0 ? ($so_ve_ban / $so_ghe_toi_da) * 100 : 0;

    // Lấy thông tin thù lao
    $query_phan_cong = "SELECT pc.*, tx.HoTen, tx.SDT,
                           CASE pc.VaiTro
                               WHEN 1 THEN 'Tài xế chính'
                               WHEN 2 THEN 'Lái phụ'
                               ELSE 'Không xác định'
                           END as TenVaiTro
                    FROM phan_cong pc
                    LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
                    WHERE pc.MaChuyenXe = ?
                    ORDER BY pc.VaiTro";
    $stmt_phan_cong = $db->prepare($query_phan_cong);
    $stmt_phan_cong->execute([$id]);
    $phan_cong = $stmt_phan_cong->fetchAll(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết chuyến xe</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Chuyến xe</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($chuyen_xe['MaChuyenXe']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <?php if ($chuyen_xe['TrangThai'] == 1): ?>
                    <a href="edit.php?id=<?php echo $chuyen_xe['MaChuyenXe']; ?>" class="btn btn-warning me-2">
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
            <!-- Thông tin chuyến xe -->
            <div class="col-lg-4 mb-4">
                <div class="card mt-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>Thông tin chuyến
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-route fa-3x"></i>
                            </div>
                        </div>

                        <h4 class="text-primary mb-3"><?php echo htmlspecialchars($chuyen_xe['MaChuyenXe']); ?></h4>

                        <!-- Trạng thái -->
                        <div class="mb-4">
                            <?php
                            $badge_class = $chuyen_xe['TrangThai'] == 1 ? 'bg-warning text-dark' : 'bg-success';
                            $now = time();
                            $departure = strtotime($chuyen_xe['GioDi']);

                            if ($chuyen_xe['TrangThai'] == 1 && $departure <= $now) {
                                $badge_class = 'bg-danger';
                                $status_text = 'Trễ giờ khởi hành';
                            } else {
                                $status_text = $chuyen_xe['TenTrangThai'];
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?> fs-6">
                                <?php echo $status_text; ?>
                            </span>
                        </div>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Tuyến:</td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($chuyen_xe['MaTuyenDuong']); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Xe:</td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($chuyen_xe['MaXe']); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Loại xe:</td>
                                <td><?php echo htmlspecialchars($chuyen_xe['TenLoaiXe']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Khoảng cách:</td>
                                <td><?php echo number_format($chuyen_xe['DoDai'], 1); ?> km</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Tình trạng bán vé -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie text-success me-2"></i>Tình trạng bán vé
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-6">
                                <h3 class="text-success"><?php echo $so_ve_ban; ?></h3>
                                <small class="text-muted">Đã bán</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-info"><?php echo $so_ghe_trong; ?></h3>
                                <small class="text-muted">Còn trống</small>
                            </div>
                        </div>

                        <div class="progress mt-3 mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $ti_le_ban; ?>%"></div>
                        </div>
                        <small class="text-muted">
                            <?php echo number_format($ti_le_ban, 1); ?>% ghế đã bán
                        </small>
                    </div>
                </div>
            </div>

            <!-- Chi tiết và hoạt động -->
            <div class="col-lg-8">
                <!-- Lịch trình -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-warning me-2"></i>Lịch trình
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($chuyen_xe['DiemDau']); ?></h6>
                                        <small class="text-muted">Điểm khởi hành</small><br>
                                        <strong class="text-success">
                                            <?php echo date('d/m/Y H:i', strtotime($chuyen_xe['GioDi'])); ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-stop"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($chuyen_xe['DiemCuoi']); ?></h6>
                                        <small class="text-muted">Điểm đến</small><br>
                                        <strong class="text-danger">
                                            <?php echo date('d/m/Y H:i', strtotime($chuyen_xe['GioDen'])); ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3 pt-3 border-top">
                            <div class="col-md-4 text-center">
                                <div class="text-info mb-2">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h6>Thời gian di chuyển</h6>
                                <span class="badge bg-info">
                                    <?php
                                    $duration = (strtotime($chuyen_xe['GioDen']) - strtotime($chuyen_xe['GioDi'])) / 3600;
                                    echo number_format($duration, 1) . ' giờ';
                                    ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="text-secondary mb-2">
                                    <i class="fas fa-tachometer-alt fa-2x"></i>
                                </div>
                                <h6>Tốc độ TB</h6>
                                <span class="badge bg-secondary">
                                    <?php echo number_format($chuyen_xe['DoDai'] / $duration, 0); ?> km/h
                                </span>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-level-up-alt fa-2x"></i>
                                </div>
                                <h6>Độ phức tạp</h6>
                                <?php
                                $phuc_tap_class = '';
                                $phuc_tap_text = '';
                                switch($chuyen_xe['DoPhucTap']) {
                                    case 1: $phuc_tap_class = 'bg-success'; $phuc_tap_text = 'Đơn giản'; break;
                                    case 2: $phuc_tap_class = 'bg-warning text-dark'; $phuc_tap_text = 'Trung bình'; break;
                                    case 3: $phuc_tap_class = 'bg-danger'; $phuc_tap_text = 'Phức tạp'; break;
                                }
                                ?>
                                <span class="badge <?php echo $phuc_tap_class; ?>"><?php echo $phuc_tap_text; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phân công tài xế -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-tie text-info me-2"></i>
                                    Phân công tài xế
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../phan_cong/create.php?chuyen_xe=<?php echo $chuyen_xe['MaChuyenXe']; ?>"
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i>Phân công
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($phan_cong)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-times fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Chưa có tài xế được phân công</p>
                                <a href="../phan_cong/create.php?chuyen_xe=<?php echo $chuyen_xe['MaChuyenXe']; ?>"
                                   class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Phân công tài xế
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($phan_cong as $pc): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($pc['HoTen']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($pc['MaTaiXe']); ?></small><br>
                                                        <span class="badge <?php echo $pc['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary'; ?>">
                                                    <?php echo $pc['TenVaiTro']; ?>
                                                </span>
                                                        <span class="badge bg-success">
                                                    <?php echo number_format($pc['ThuLao'], 0, ',', '.'); ?>đ
                                                </span>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <a href="tel:<?php echo $pc['SDT']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-phone me-1"></i><?php echo $pc['SDT']; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Danh sách vé -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-ticket-alt text-success me-2"></i>
                                    Danh sách vé đã bán (<?php echo count($danh_sach_ve); ?>)
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../ve_xe/create.php?chuyen_xe=<?php echo $chuyen_xe['MaChuyenXe']; ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i>Bán vé
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($danh_sach_ve)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Chưa có vé nào được bán</p>
                                <a href="../ve_xe/create.php?chuyen_xe=<?php echo $chuyen_xe['MaChuyenXe']; ?>"
                                   class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Bán vé đầu tiên
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Mã vé</th>
                                        <th>Vị trí</th>
                                        <th>Hành khách</th>
                                        <th>SĐT</th>
                                        <th>Giá vé</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($danh_sach_ve as $ve): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary"><?php echo htmlspecialchars($ve['MaVe']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($ve['ViTri']); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($ve['TenHanhKhach']); ?></div>
                                            </td>
                                            <td>
                                                <a href="tel:<?php echo $ve['SDTHanhKhach']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($ve['SDTHanhKhach']); ?>
                                                </a>
                                            </td>
                                            <td>
                                            <span class="fw-bold text-success">
                                                <?php echo $ve['GiaVeThucTe'] ? number_format($ve['GiaVeThucTe'], 0, ',', '.') . 'đ' : 'Chưa cập nhật'; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../ve_xe/view.php?id=<?php echo $ve['MaVe']; ?>"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../ve_xe/edit.php?id=<?php echo $ve['MaVe']; ?>"
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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
