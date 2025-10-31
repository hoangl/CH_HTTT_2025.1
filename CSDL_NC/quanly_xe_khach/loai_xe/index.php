<?php
$page_title = "Quản lý loại xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Kiểm tra xem có xe nào đang sử dụng loại xe này không
        $check_query = "SELECT COUNT(*) FROM xe_khach WHERE MaLoaiXe = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            $error = "Không thể xóa loại xe này vì đang có xe sử dụng!";
        } else {
            $query = "DELETE FROM loai_xe WHERE MaLoaiXe = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Xóa loại xe thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách loại xe
try {
    $query = "SELECT lx.*, 
                     COUNT(xk.MaXe) as SoLuongXe,
                     CASE 
                         WHEN lx.SoGhe <= 16 THEN 'Xe nhỏ'
                         WHEN lx.SoGhe <= 29 THEN 'Xe trung'
                         ELSE 'Xe lớn'
                     END as PhanLoai
              FROM loai_xe lx 
              LEFT JOIN xe_khach xk ON lx.MaLoaiXe = xk.MaLoaiXe 
              GROUP BY lx.MaLoaiXe 
              ORDER BY lx.MaLoaiXe";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $loai_xe_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-car text-primary me-2"></i>Quản lý loại xe</h2>
                <p class="text-muted mb-0">Quản lý các loại xe trong hệ thống</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Thêm loại xe
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
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách loại xe</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($loai_xe_list); ?> loại xe</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($loai_xe_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-car fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có loại xe nào được thêm</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Thêm loại xe đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="15%">Mã loại xe</th>
                                <th width="25%">Tên loại xe</th>
                                <th width="15%">Số ghế</th>
                                <th width="15%">Phân loại</th>
                                <th width="15%">Số xe đang dùng</th>
                                <th width="15%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($loai_xe_list as $loai_xe): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($loai_xe['MaLoaiXe']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($loai_xe['TenLoaiXe']); ?></div>
                                    </td>
                                    <td>
                                    <span class="badge bg-info fs-6">
                                        <i class="fas fa-chair me-1"></i><?php echo $loai_xe['SoGhe']; ?> ghế
                                    </span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        switch($loai_xe['PhanLoai']) {
                                            case 'Xe nhỏ': $badge_class = 'bg-success'; break;
                                            case 'Xe trung': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Xe lớn': $badge_class = 'bg-primary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $loai_xe['PhanLoai']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <?php if ($loai_xe['SoLuongXe'] > 0): ?>
                                            <span class="badge bg-secondary">
                                            <i class="fas fa-bus me-1"></i><?php echo $loai_xe['SoLuongXe']; ?> xe
                                        </span>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có xe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $loai_xe['MaLoaiXe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $loai_xe['MaLoaiXe']; ?>"
                                               class="btn btn-sm btn-outline-warning"
                                               data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($loai_xe['SoLuongXe'] == 0): ?>
                                                <a href="index.php?delete=<?php echo $loai_xe['MaLoaiXe']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa loại xe '<?php echo htmlspecialchars($loai_xe['TenLoaiXe']); ?>'?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Không thể xóa vì đang có xe sử dụng">
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
