<?php
$page_title = "Chi tiết loại xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin loại xe và số lượng xe đang sử dụng
try {
    $query = "SELECT lx.*, COUNT(xk.MaXe) as SoLuongXe
              FROM loai_xe lx 
              LEFT JOIN xe_khach xk ON lx.MaLoaiXe = xk.MaLoaiXe 
              WHERE lx.MaLoaiXe = ?
              GROUP BY lx.MaLoaiXe";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $loai_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loai_xe) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách xe đang sử dụng loại xe này
    $query_xe = "SELECT xk.*, 
                        CASE 
                            WHEN xk.SoNgayBDConLai <= 0 THEN 'Cần bảo dưỡng'
                            WHEN xk.HanDangKiem <= CURDATE() THEN 'Hết hạn đăng kiểm'
                            ELSE 'Hoạt động'
                        END as TrangThai
                 FROM xe_khach xk 
                 WHERE xk.MaLoaiXe = ? 
                 ORDER BY xk.MaXe";
    $stmt_xe = $db->prepare($query_xe);
    $stmt_xe->execute([$id]);
    $danh_sach_xe = $stmt_xe->fetchAll(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết loại xe</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Loại xe</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($loai_xe['MaLoaiXe']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="edit.php?id=<?php echo $loai_xe['MaLoaiXe']; ?>" class="btn btn-warning me-2">
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
            <!-- Thông tin cơ bản -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-info me-2"></i>Thông tin cơ bản
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                        </div>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Mã loại xe:</td>
                                <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($loai_xe['MaLoaiXe']); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Tên loại xe:</td>
                                <td><?php echo htmlspecialchars($loai_xe['TenLoaiXe']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Số ghế:</td>
                                <td><span class="badge bg-info fs-6">
                                    <i class="fas fa-chair me-1"></i><?php echo $loai_xe['SoGhe']; ?> ghế
                                </span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Phân loại:</td>
                                <td>
                                    <?php
                                    $so_ghe = $loai_xe['SoGhe'];
                                    if ($so_ghe <= 16) {
                                        echo '<span class="badge bg-success">Xe nhỏ (≤16 ghế)</span>';
                                    } elseif ($so_ghe <= 29) {
                                        echo '<span class="badge bg-warning text-dark">Xe trung (17-29 ghế)</span>';
                                    } else {
                                        echo '<span class="badge bg-primary">Xe lớn (≥30 ghế)</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Số xe sử dụng:</td>
                                <td>
                                    <span class="badge bg-secondary fs-6">
                                        <i class="fas fa-bus me-1"></i><?php echo $loai_xe['SoLuongXe']; ?> xe
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Danh sách xe sử dụng -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-bus text-primary me-2"></i>
                                    Danh sách xe đang sử dụng loại này
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../xe_khach/create.php?loai_xe=<?php echo $loai_xe['MaLoaiXe']; ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i>Thêm xe
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($danh_sach_xe)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Chưa có xe nào sử dụng loại xe này</p>
                                <a href="../xe_khach/create.php?loai_xe=<?php echo $loai_xe['MaLoaiXe']; ?>"
                                   class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Thêm xe đầu tiên
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Biển số xe</th>
                                        <th>Bảo dưỡng</th>
                                        <th>Đăng kiểm</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($danh_sach_xe as $xe): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($xe['MaXe']); ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $ngay_con_lai = $xe['SoNgayBDConLai'];
                                                $badge_class = $ngay_con_lai > 30 ? 'bg-success' : ($ngay_con_lai > 10 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $ngay_con_lai; ?> ngày
                                            </span>
                                            </td>
                                            <td>
                                                <?php
                                                $han_dk = new DateTime($xe['HanDangKiem']);
                                                $now = new DateTime();
                                                $badge_class = $han_dk > $now ? 'bg-success' : 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo date('d/m/Y', strtotime($xe['HanDangKiem'])); ?>
                                            </span>
                                            </td>
                                            <td>
                                                <?php
                                                switch($xe['TrangThai']) {
                                                    case 'Hoạt động':
                                                        echo '<span class="badge bg-success">Hoạt động</span>';
                                                        break;
                                                    case 'Cần bảo dưỡng':
                                                        echo '<span class="badge bg-danger">Cần bảo dưỡng</span>';
                                                        break;
                                                    case 'Hết hạn đăng kiểm':
                                                        echo '<span class="badge bg-warning text-dark">Hết hạn ĐK</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../xe_khach/view.php?id=<?php echo $xe['MaXe']; ?>"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../xe_khach/edit.php?id=<?php echo $xe['MaXe']; ?>"
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

        <!-- Thống kê -->
        <?php if (!empty($danh_sach_xe)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar text-success me-2"></i>
                                Thống kê trạng thái xe
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $hoat_dong = 0;
                            $can_bao_duong = 0;
                            $het_han_dk = 0;

                            foreach ($danh_sach_xe as $xe) {
                                switch($xe['TrangThai']) {
                                    case 'Hoạt động': $hoat_dong++; break;
                                    case 'Cần bảo dưỡng': $can_bao_duong++; break;
                                    case 'Hết hạn đăng kiểm': $het_han_dk++; break;
                                }
                            }
                            ?>
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="border rounded p-3">
                                        <h3 class="text-success mb-2"><?php echo $hoat_dong; ?></h3>
                                        <p class="text-muted mb-0">Xe hoạt động tốt</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3">
                                        <h3 class="text-danger mb-2"><?php echo $can_bao_duong; ?></h3>
                                        <p class="text-muted mb-0">Xe cần bảo dưỡng</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3">
                                        <h3 class="text-warning mb-2"><?php echo $het_han_dk; ?></h3>
                                        <p class="text-muted mb-0">Xe hết hạn đăng kiểm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
