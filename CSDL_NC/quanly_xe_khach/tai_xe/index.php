<?php
$page_title = "Quản lý tài xế";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Kiểm tra tài xế có đang được phân công không
        $check_query = "SELECT COUNT(*) FROM phan_cong WHERE MaTaiXe = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            $error = "Không thể xóa tài xế này vì đang có phân công!";
        } else {
            $query = "DELETE FROM tai_xe WHERE MaTaiXe = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Xóa tài xế thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách tài xế với thống kê
try {
    $query = "SELECT tx.*, 
                     COUNT(pc.MaChuyenXe) as SoChuyenXe,
                     COALESCE(SUM(pc.ThuLao), 0) as TongThuLao,
                     YEAR(CURDATE()) - YEAR(tx.NgaySinh) - 
                     (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(tx.NgaySinh, '%m%d')) as Tuoi
              FROM tai_xe tx
              LEFT JOIN phan_cong pc ON tx.MaTaiXe = pc.MaTaiXe
              GROUP BY tx.MaTaiXe
              ORDER BY tx.MaTaiXe";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tai_xe_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-user-tie text-primary me-2"></i>Quản lý tài xế</h2>
                <p class="text-muted mb-0">Quản lý thông tin tài xế và lái phụ</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Thêm tài xế
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
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách tài xế</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($tai_xe_list); ?> tài xế</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tai_xe_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có tài xế nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Thêm tài xế đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="10%">Mã tài xế</th>
                                <th width="20%">Họ tên</th>
                                <th width="15%">SĐT</th>
                                <th width="10%">Tuổi</th>
                                <th width="15%">Địa chỉ</th>
                                <th width="10%">Số chuyến</th>
                                <th width="10%">Thu lao</th>
                                <th width="10%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tai_xe_list as $tai_xe): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($tai_xe['MaTaiXe']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($tai_xe['HoTen']); ?></div>
                                        <small class="text-muted">Sinh: <?php echo date('d/m/Y', strtotime($tai_xe['NgaySinh'])); ?></small>
                                    </td>
                                    <td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($tai_xe['SDT']); ?>
                                    </span>
                                    </td>
                                    <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $tai_xe['Tuoi']; ?> tuổi
                                    </span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($tai_xe['DiaChi']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($tai_xe['SoChuyenXe'] > 0): ?>
                                            <span class="badge bg-primary">
                                            <i class="fas fa-route me-1"></i><?php echo $tai_xe['SoChuyenXe']; ?>
                                        </span>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                    <span class="fw-bold text-success">
                                        <?php echo number_format($tai_xe['TongThuLao'], 0, ',', '.'); ?>đ
                                    </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $tai_xe['MaTaiXe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $tai_xe['MaTaiXe']; ?>"
                                               class="btn btn-sm btn-outline-warning"
                                               data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($tai_xe['SoChuyenXe'] == 0): ?>
                                                <a href="index.php?delete=<?php echo $tai_xe['MaTaiXe']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa tài xế '<?php echo htmlspecialchars($tai_xe['HoTen']); ?>'?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Không thể xóa vì đang có phân công">
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
