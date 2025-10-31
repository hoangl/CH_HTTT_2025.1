<?php
$page_title = "Quản lý tuyến đường";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Kiểm tra tuyến đường có đang được sử dụng không
        $check_query = "SELECT COUNT(*) FROM chuyen_xe WHERE MaTuyenDuong = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $count_chuyen = $check_stmt->fetchColumn();

        $check_query2 = "SELECT COUNT(*) FROM bang_gia WHERE MaTuyenDuong = ?";
        $check_stmt2 = $db->prepare($check_query2);
        $check_stmt2->execute([$id]);
        $count_gia = $check_stmt2->fetchColumn();

        if ($count_chuyen > 0 || $count_gia > 0) {
            $error = "Không thể xóa tuyến đường này vì đang có chuyến xe hoặc bảng giá sử dụng!";
        } else {
            $query = "DELETE FROM tuyen_duong WHERE MaTuyenDuong = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Xóa tuyến đường thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách tuyến đường với thống kê
try {
    $query = "SELECT td.*, 
                     COUNT(DISTINCT cx.MaChuyenXe) as SoChuyenXe,
                     COUNT(DISTINCT bg.MaLoaiXe) as SoBangGia,
                     CASE 
                         WHEN td.DoPhucTap = 1 THEN 'Đơn giản'
                         WHEN td.DoPhucTap = 2 THEN 'Trung bình'
                         WHEN td.DoPhucTap = 3 THEN 'Phức tạp'
                         ELSE 'Không xác định'
                     END as TenDoPhucTap
              FROM tuyen_duong td
              LEFT JOIN chuyen_xe cx ON td.MaTuyenDuong = cx.MaTuyenDuong
              LEFT JOIN bang_gia bg ON td.MaTuyenDuong = bg.MaTuyenDuong
              GROUP BY td.MaTuyenDuong
              ORDER BY td.MaTuyenDuong";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tuyen_duong_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-route text-primary me-2"></i>Quản lý tuyến đường</h2>
                <p class="text-muted mb-0">Quản lý các tuyến đường vận chuyển</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Thêm tuyến đường
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
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách tuyến đường</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($tuyen_duong_list); ?> tuyến</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tuyen_duong_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-route fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có tuyến đường nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Thêm tuyến đường đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="10%">Mã tuyến</th>
                                <th width="25%">Tuyến đường</th>
                                <th width="12%">Độ dài</th>
                                <th width="12%">Độ phức tạp</th>
                                <th width="12%">Số chuyến</th>
                                <th width="12%">Bảng giá</th>
                                <th width="17%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tuyen_duong_list as $tuyen): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($tuyen['MaTuyenDuong']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($tuyen['DiemDau']); ?></div>
                                        <div class="text-primary">
                                            <i class="fas fa-arrow-down me-1"></i>
                                        </div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($tuyen['DiemCuoi']); ?></div>
                                    </td>
                                    <td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-ruler me-1"></i>
                                        <?php echo number_format($tuyen['DoDai'], 0, ',', '.'); ?> km
                                    </span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        switch($tuyen['DoPhucTap']) {
                                            case 1: $badge_class = 'bg-success'; break;
                                            case 2: $badge_class = 'bg-warning text-dark'; break;
                                            case 3: $badge_class = 'bg-danger'; break;
                                            default: $badge_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $tuyen['TenDoPhucTap']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <?php if ($tuyen['SoChuyenXe'] > 0): ?>
                                            <span class="badge bg-primary">
                                            <i class="fas fa-bus me-1"></i><?php echo $tuyen['SoChuyenXe']; ?>
                                        </span>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tuyen['SoBangGia'] > 0): ?>
                                            <span class="badge bg-success">
                                            <i class="fas fa-tags me-1"></i><?php echo $tuyen['SoBangGia']; ?>
                                        </span>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $tuyen['MaTuyenDuong']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $tuyen['MaTuyenDuong']; ?>"
                                               class="btn btn-sm btn-outline-warning"
                                               data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($tuyen['SoChuyenXe'] == 0 && $tuyen['SoBangGia'] == 0): ?>
                                                <a href="index.php?delete=<?php echo $tuyen['MaTuyenDuong']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa tuyến đường '<?php echo htmlspecialchars($tuyen['DiemDau'] . ' - ' . $tuyen['DiemCuoi']); ?>'?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Không thể xóa vì đang sử dụng">
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
