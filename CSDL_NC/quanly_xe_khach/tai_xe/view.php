<?php
$page_title = "Chi tiết tài xế";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin tài xế chi tiết
try {
    $query = "SELECT *, 
                     YEAR(CURDATE()) - YEAR(NgaySinh) - 
                     (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(NgaySinh, '%m%d')) as Tuoi
              FROM tai_xe WHERE MaTaiXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $tai_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tai_xe) {
        header("Location: index.php");
        exit();
    }

    // Lấy lịch sử phân công
    $query_phan_cong = "SELECT pc.*, cx.GioDi, cx.GioDen, 
                               td.DiemDau, td.DiemCuoi, xk.MaXe,
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
                        LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
                        WHERE pc.MaTaiXe = ?
                        ORDER BY cx.GioDi DESC
                        LIMIT 20";
    $stmt_phan_cong = $db->prepare($query_phan_cong);
    $stmt_phan_cong->execute([$id]);
    $lich_su_phan_cong = $stmt_phan_cong->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê
    $query_stats = "SELECT 
                        COUNT(pc.MaChuyenXe) as TongChuyenXe,
                        COALESCE(SUM(pc.ThuLao), 0) as TongThuLao,
                        COUNT(CASE WHEN pc.VaiTro = 1 THEN 1 END) as ChuyenTaiXeChinh,
                        COUNT(CASE WHEN pc.VaiTro = 2 THEN 1 END) as ChuyenLaiPhu,
                        COUNT(CASE WHEN cx.TrangThai = 2 THEN 1 END) as ChuyenHoanThanh
                    FROM phan_cong pc
                    LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
                    WHERE pc.MaTaiXe = ?";
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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết tài xế</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Tài xế</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($tai_xe['MaTaiXe']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="edit.php?id=<?php echo $tai_xe['MaTaiXe']; ?>" class="btn btn-warning me-2">
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
            <!-- Thông tin tài xế -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user text-primary me-2"></i>Thông tin cá nhân
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-user-tie fa-3x"></i>
                            </div>
                        </div>

                        <h4 class="text-primary mb-3"><?php echo htmlspecialchars($tai_xe['HoTen']); ?></h4>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Mã tài xế:</td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($tai_xe['MaTaiXe']); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Tuổi:</td>
                                <td><span class="badge bg-secondary"><?php echo $tai_xe['Tuoi']; ?> tuổi</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Sinh ngày:</td>
                                <td><?php echo date('d/m/Y', strtotime($tai_xe['NgaySinh'])); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">SĐT:</td>
                                <td>
                                    <a href="tel:<?php echo $tai_xe['SDT']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($tai_xe['SDT']); ?>
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <div class="mt-3">
                            <strong class="text-muted">Địa chỉ:</strong><br>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($tai_xe['DiaChi']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thống kê và hoạt động -->
            <div class="col-lg-8">
                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-2-5 col-6 mb-3">
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
                    <div class="col-md-2-5 col-6 mb-3">
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
                    <div class="col-md-2-5 col-6 mb-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-steering-wheel fa-2x"></i>
                                </div>
                                <h4 class="text-warning"><?php echo $stats['ChuyenTaiXeChinh']; ?></h4>
                                <small class="text-muted">Tài xế chính</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2-5 col-6 mb-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <div class="text-info mb-2">
                                    <i class="fas fa-user-friends fa-2x"></i>
                                </div>
                                <h4 class="text-info"><?php echo $stats['ChuyenLaiPhu']; ?></h4>
                                <small class="text-muted">Lái phụ</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2-5 col-6 mb-3">
                        <div class="card text-center border-danger">
                            <div class="card-body">
                                <div class="text-danger mb-2">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                                <h4 class="text-danger"><?php echo number_format($stats['TongThuLao'], 0, ',', '.'); ?>đ</h4>
                                <small class="text-muted">Tổng thu lao</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử phân công -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-history text-info me-2"></i>
                                    Lịch sử phân công (20 chuyến gần nhất)
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../phan_cong/create.php?tai_xe=<?php echo $tai_xe['MaTaiXe']; ?>"
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i>Phân công mới
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lich_su_phan_cong)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Tài xế chưa được phân công chuyến nào</p>
                                <a href="../phan_cong/create.php?tai_xe=<?php echo $tai_xe['MaTaiXe']; ?>"
                                   class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Phân công chuyến đầu tiên
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Chuyến xe</th>
                                        <th>Tuyến đường</th>
                                        <th>Xe</th>
                                        <th>Thời gian</th>
                                        <th>Vai trò</th>
                                        <th>Thu lao</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($lich_su_phan_cong as $phan_cong): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($phan_cong['MaChuyenXe']); ?></small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <strong><?php echo htmlspecialchars($phan_cong['DiemDau']); ?></strong><br>
                                                    <i class="fas fa-arrow-down text-primary"></i><br>
                                                    <strong><?php echo htmlspecialchars($phan_cong['DiemCuoi']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($phan_cong['MaXe']); ?></span>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <strong>Đi:</strong> <?php echo date('d/m H:i', strtotime($phan_cong['GioDi'])); ?><br>
                                                    <strong>Đến:</strong> <?php echo date('d/m H:i', strtotime($phan_cong['GioDen'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $vai_tro_class = $phan_cong['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $vai_tro_class; ?>">
                                                <?php echo $phan_cong['TenVaiTro']; ?>
                                            </span>
                                            </td>
                                            <td>
                                            <span class="fw-bold text-success">
                                                <?php echo number_format($phan_cong['ThuLao'], 0, ',', '.'); ?>đ
                                            </span>
                                            </td>
                                            <td>
                                                <?php
                                                $trang_thai_class = $phan_cong['TenTrangThai'] == 'Hoàn thành' ? 'bg-success' : 'bg-warning text-dark';
                                                ?>
                                                <span class="badge <?php echo $trang_thai_class; ?>">
                                                <?php echo $phan_cong['TenTrangThai']; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../chuyen_xe/view.php?id=<?php echo $phan_cong['MaChuyenXe']; ?>"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($phan_cong['TenTrangThai'] != 'Hoàn thành'): ?>
                                                        <a href="../phan_cong/edit.php?chuyen=<?php echo $phan_cong['MaChuyenXe']; ?>&tai_xe=<?php echo $phan_cong['MaTaiXe']; ?>"
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

<style>
    .col-md-2-5 {
        flex: 0 0 20%;
        max-width: 20%;
    }

    @media (max-width: 768px) {
        .col-md-2-5 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
