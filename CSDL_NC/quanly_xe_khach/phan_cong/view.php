<?php
$page_title = "Chi tiết phân công";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy thông tin từ URL
$ma_chuyen_xe = isset($_GET['chuyen']) ? $_GET['chuyen'] : '';
$ma_tai_xe = isset($_GET['tai_xe']) ? $_GET['tai_xe'] : '';

if (empty($ma_chuyen_xe) || empty($ma_tai_xe)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin phân công chi tiết
try {
    $query = "SELECT pc.*, 
                     cx.GioDi, cx.GioDen, cx.TrangThai,
                     td.DiemDau, td.DiemCuoi, td.DoDai, td.DoPhucTap,
                     xk.MaXe, lx.TenLoaiXe, lx.SoGhe,
                     tx.HoTen, tx.SDT, tx.NgaySinh, tx.DiaChi,
                     YEAR(CURDATE()) - YEAR(tx.NgaySinh) - 
                     (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(tx.NgaySinh, '%m%d')) as Tuoi,
                     CASE pc.VaiTro
                         WHEN 1 THEN 'Tài xế chính'
                         WHEN 2 THEN 'Lái phụ'
                         ELSE 'Không xác định'
                     END as TenVaiTro,
                     CASE cx.TrangThai 
                         WHEN 1 THEN 'Chờ khởi hành'
                         WHEN 2 THEN 'Hoàn thành'
                         ELSE 'Không xác định'
                     END as TenTrangThaiChuyen
              FROM phan_cong pc
              LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
              WHERE pc.MaChuyenXe = ? AND pc.MaTaiXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$ma_chuyen_xe, $ma_tai_xe]);
    $phan_cong = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phan_cong) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách tất cả phân công trong chuyến xe này
    $query_team = "SELECT pc.*, tx.HoTen, tx.SDT,
                          CASE pc.VaiTro
                              WHEN 1 THEN 'Tài xế chính'
                              WHEN 2 THEN 'Lái phụ'
                              ELSE 'Không xác định'
                          END as TenVaiTro
                   FROM phan_cong pc
                   LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
                   WHERE pc.MaChuyenXe = ?
                   ORDER BY pc.VaiTro";
    $stmt_team = $db->prepare($query_team);
    $stmt_team->execute([$ma_chuyen_xe]);
    $team_members = $stmt_team->fetchAll(PDO::FETCH_ASSOC);

    // Lấy lịch sử phân công của tài xế này
    $query_history = "SELECT pc.*, cx.GioDi, cx.GioDen,
                             td.DiemDau, td.DiemCuoi, td.DoDai,
                             CASE pc.VaiTro
                                 WHEN 1 THEN 'Tài xế chính'
                                 WHEN 2 THEN 'Lái phụ'
                                 ELSE 'Không xác định'
                             END as TenVaiTro,
                             CASE cx.TrangThai 
                                 WHEN 1 THEN 'Chờ khởi hành'
                                 WHEN 2 THEN 'Hoàn thành'
                                 ELSE 'Không xác định'
                             END as TenTrangThai
                      FROM phan_cong pc
                      LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
                      LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
                      WHERE pc.MaTaiXe = ? AND pc.MaChuyenXe != ?
                      ORDER BY cx.GioDi DESC
                      LIMIT 10";
    $stmt_history = $db->prepare($query_history);
    $stmt_history->execute([$ma_tai_xe, $ma_chuyen_xe]);
    $history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết phân công</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Phân công</a></li>
                        <li class="breadcrumb-item active">Chi tiết</li>
                    </ol>
                </nav>
            </div>
            <div>
                <?php if ($phan_cong['TrangThai'] == 1): ?>
                    <a href="edit.php?chuyen=<?php echo $phan_cong['MaChuyenXe']; ?>&tai_xe=<?php echo $phan_cong['MaTaiXe']; ?>" class="btn btn-warning me-2">
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
            <!-- Thông tin phân công -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check text-primary me-2"></i>Thông tin phân công
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-user-tie fa-3x"></i>
                            </div>
                        </div>

                        <h4 class="text-primary mb-3"><?php echo htmlspecialchars($phan_cong['HoTen']); ?></h4>

                        <!-- Vai trò -->
                        <div class="mb-4">
                            <?php
                            $vai_tro_class = $phan_cong['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $vai_tro_class; ?> fs-6">
                                <?php echo $phan_cong['TenVaiTro']; ?>
                            </span>
                        </div>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Mã tài xế:</td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($phan_cong['MaTaiXe']); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Tuổi:</td>
                                <td><span class="badge bg-secondary"><?php echo $phan_cong['Tuoi']; ?> tuổi</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">SĐT:</td>
                                <td>
                                    <a href="tel:<?php echo $phan_cong['SDT']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($phan_cong['SDT']); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Thu lao:</td>
                                <td>
                                    <span class="fw-bold text-success fs-5">
                                        <?php echo number_format($phan_cong['ThuLao'], 0, ',', '.'); ?>đ
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <div class="mt-3">
                            <strong class="text-muted">Địa chỉ:</strong><br>
                            <p class="text-muted mb-0 small"><?php echo htmlspecialchars($phan_cong['DiaChi']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chi tiết chuyến xe và team -->
            <div class="col-lg-8">
                <!-- Thông tin chuyến xe -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-route text-warning me-2"></i>
                            Thông tin chuyến xe
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold text-muted">Mã chuyến:</td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($phan_cong['MaChuyenXe']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">Xe:</td>
                                        <td>
                                            <span class="fw-bold"><?php echo htmlspecialchars($phan_cong['MaXe']); ?></span><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($phan_cong['TenLoaiXe']); ?></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">Khoảng cách:</td>
                                        <td><?php echo number_format($phan_cong['DoDai'], 1); ?> km</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                            <span class="badge bg-success me-2"><?php echo htmlspecialchars($phan_cong['DiemDau']); ?></span>
                                            <i class="fas fa-arrow-right text-primary"></i>
                                            <span class="badge bg-danger ms-2"><?php echo htmlspecialchars($phan_cong['DiemCuoi']); ?></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-success mb-2">
                                                <i class="fas fa-play fa-lg"></i>
                                            </div>
                                            <small class="text-muted">Khởi hành</small><br>
                                            <strong><?php echo date('d/m H:i', strtotime($phan_cong['GioDi'])); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-danger mb-2">
                                                <i class="fas fa-stop fa-lg"></i>
                                            </div>
                                            <small class="text-muted">Dự kiến đến</small><br>
                                            <strong><?php echo date('d/m H:i', strtotime($phan_cong['GioDen'])); ?></strong>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <?php
                                        $trang_thai_class = $phan_cong['TrangThai'] == 1 ? 'bg-warning text-dark' : 'bg-success';
                                        ?>
                                        <span class="badge <?php echo $trang_thai_class; ?>">
                                            <?php echo $phan_cong['TenTrangThaiChuyen']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đội ngũ chuyến xe -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-success me-2"></i>
                            Đội ngũ chuyến xe (<?php echo count($team_members); ?> người)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($team_members as $member): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light <?php echo $member['MaTaiXe'] == $ma_tai_xe ? 'border-primary' : ''; ?>">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?php echo htmlspecialchars($member['HoTen']); ?>
                                                        <?php if ($member['MaTaiXe'] == $ma_tai_xe): ?>
                                                            <span class="badge bg-primary small">Hiện tại</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($member['MaTaiXe']); ?></small><br>
                                                    <span class="badge <?php echo $member['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary'; ?> small">
                                                    <?php echo $member['TenVaiTro']; ?>
                                                </span>
                                                    <span class="badge bg-success small">
                                                    <?php echo number_format($member['ThuLao'], 0, ',', '.'); ?>đ
                                                </span>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <a href="tel:<?php echo $member['SDT']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-phone me-1"></i><?php echo $member['SDT']; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Tổng thu lao -->
                        <div class="mt-3 pt-3 border-top">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Tổng thu lao đội ngũ:</strong>
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="fw-bold text-success fs-5">
                                        <?php
                                        $total_salary = array_sum(array_column($team_members, 'ThuLao'));
                                        echo number_format($total_salary, 0, ',', '.');
                                        ?>đ
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử phân công -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history text-info me-2"></i>
                            Lịch sử phân công của tài xế (10 chuyến gần nhất)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($history)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Đây là chuyến đầu tiên của tài xế này</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Chuyến xe</th>
                                        <th>Tuyến đường</th>
                                        <th>Thời gian</th>
                                        <th>Vai trò</th>
                                        <th>Thu lao</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($history as $item): ?>
                                        <tr>
                                            <td>
                                                <small class="fw-bold text-primary"><?php echo htmlspecialchars($item['MaChuyenXe']); ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo htmlspecialchars($item['DiemDau']); ?> → <?php echo htmlspecialchars($item['DiemCuoi']); ?><br>
                                                    <span class="text-muted"><?php echo number_format($item['DoDai'], 0); ?>km</span>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('d/m H:i', strtotime($item['GioDi'])); ?> -<br>
                                                    <?php echo date('d/m H:i', strtotime($item['GioDen'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                            <span class="badge <?php echo $item['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary'; ?> small">
                                                <?php echo $item['TenVaiTro']; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <small class="fw-bold text-success">
                                                    <?php echo number_format($item['ThuLao'], 0, ',', '.'); ?>đ
                                                </small>
                                            </td>
                                            <td>
                                            <span class="badge <?php echo $item['TenTrangThai'] == 'Hoàn thành' ? 'bg-success' : 'bg-warning text-dark'; ?> small">
                                                <?php echo $item['TenTrangThai']; ?>
                                            </span>
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
