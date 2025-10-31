<?php
$page_title = "Quản lý xe khách";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Kiểm tra xe có đang được sử dụng không
        $check_query = "SELECT COUNT(*) FROM chuyen_xe WHERE MaXe = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            $error = "Không thể xóa xe này vì đang có chuyến xe sử dụng!";
        } else {
            $query = "DELETE FROM xe_khach WHERE MaXe = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Xóa xe thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách xe khách với thông tin loại xe
try {
    $query = "SELECT xk.*, lx.TenLoaiXe, lx.SoGhe,
                     CASE 
                         WHEN xk.SoNgayBDConLai <= 0 THEN 'danger'
                         WHEN xk.SoNgayBDConLai <= 10 THEN 'warning'
                         ELSE 'success'
                     END as ColorBD,
                     CASE 
                         WHEN xk.HanDangKiem <= CURDATE() THEN 'danger'
                         WHEN DATEDIFF(xk.HanDangKiem, CURDATE()) <= 30 THEN 'warning'
                         ELSE 'success'
                     END as ColorDK,
                     COUNT(cx.MaChuyenXe) as SoChuyenXe
              FROM xe_khach xk 
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN chuyen_xe cx ON xk.MaXe = cx.MaXe
              GROUP BY xk.MaXe
              ORDER BY xk.MaXe";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $xe_khach_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-bus text-primary me-2"></i>Quản lý xe khách</h2>
                <p class="text-muted mb-0">Quản lý toàn bộ xe khách trong hệ thống</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Thêm xe khách
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
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách xe khách</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($xe_khach_list); ?> xe</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($xe_khach_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có xe khách nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Thêm xe đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="15%">Biển số xe</th>
                                <th width="20%">Loại xe</th>
                                <th width="15%">Bảo dưỡng</th>
                                <th width="15%">Đăng kiểm</th>
                                <th width="15%">Số chuyến</th>
                                <th width="10%">Trạng thái</th>
                                <th width="10%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($xe_khach_list as $xe): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary fs-6">
                                            <?php echo htmlspecialchars($xe['MaXe']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($xe['TenLoaiXe']); ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-chair me-1"></i><?php echo $xe['SoGhe']; ?> ghế
                                        </small>
                                    </td>
                                    <td>
                                    <span class="badge bg-<?php echo $xe['ColorBD']; ?>">
                                        <i class="fas fa-tools me-1"></i>
                                        <?php echo $xe['SoNgayBDConLai']; ?> ngày
                                    </span>
                                    </td>
                                    <td>
                                    <span class="badge bg-<?php echo $xe['ColorDK']; ?>">
                                        <i class="fas fa-certificate me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($xe['HanDangKiem'])); ?>
                                    </span>
                                    </td>
                                    <td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-route me-1"></i><?php echo $xe['SoChuyenXe']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($xe['SoNgayBDConLai'] <= 0 || strtotime($xe['HanDangKiem']) <= time()) {
                                            echo '<i class="fas fa-exclamation-triangle text-danger" title="Cần bảo dưỡng/đăng kiểm"></i>';
                                        } else {
                                            echo '<i class="fas fa-check-circle text-success" title="Hoạt động tốt"></i>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $xe['MaXe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $xe['MaXe']; ?>"
                                               class="btn btn-sm btn-outline-warning"
                                               data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($xe['SoChuyenXe'] == 0): ?>
                                                <a href="index.php?delete=<?php echo $xe['MaXe']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa xe '<?php echo htmlspecialchars($xe['MaXe']); ?>'?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Không thể xóa vì đang có chuyến xe">
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
