<?php
$page_title = "Xe quá hạn bảo dưỡng";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy dữ liệu xe quá hạn bảo dưỡng
$overdue_data = [];
try {
    $query = "SELECT 
                xk.MaXe,
                lx.TenLoaiXe,
                lx.SoGhe,
                xk.SoNgayBDConLai,
                ABS(xk.SoNgayBDConLai) as NgayQuaHan,
                xk.HanDangKiem,
                DATEDIFF(xk.HanDangKiem, CURDATE()) AS SoNgayDKConLai,
                -- Thống kê hoạt động
                COUNT(cx_month.MaChuyenXe) AS SoChuyenThangNay,
                COUNT(cx_week.MaChuyenXe) AS SoChuyenTuanNay,
                MAX(cx_all.GioDi) AS ChuyenCuoiCung,
                -- Tính mức độ nghiêm trọng
                CASE 
                    WHEN xk.SoNgayBDConLai <= -90 THEN 'NGUY HIỂM'
                    WHEN xk.SoNgayBDConLai <= -30 THEN 'NGHIÊM TRỌNG'
                    WHEN xk.SoNgayBDConLai <= -7 THEN 'KHẨN CẤP'
                    ELSE 'CẦN XỬ LÝ'
                END AS MucDoNghiemTrong
              FROM xe_khach xk
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN chuyen_xe cx_month ON (xk.MaXe = cx_month.MaXe 
                                              AND MONTH(cx_month.GioDi) = MONTH(CURDATE()) 
                                              AND YEAR(cx_month.GioDi) = YEAR(CURDATE()))
              LEFT JOIN chuyen_xe cx_week ON (xk.MaXe = cx_week.MaXe 
                                             AND WEEK(cx_week.GioDi) = WEEK(CURDATE()) 
                                             AND YEAR(cx_week.GioDi) = YEAR(CURDATE()))
              LEFT JOIN chuyen_xe cx_all ON xk.MaXe = cx_all.MaXe
              WHERE xk.SoNgayBDConLai <= 0
              GROUP BY xk.MaXe, lx.TenLoaiXe, lx.SoGhe, xk.SoNgayBDConLai, xk.HanDangKiem
              ORDER BY xk.SoNgayBDConLai ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $overdue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê theo mức độ nghiêm trọng
    $danger_count = 0;
    $serious_count = 0;
    $urgent_count = 0;
    $need_action_count = 0;

    foreach($overdue_data as $row) {
        switch($row['MucDoNghiemTrong']) {
            case 'NGUY HIỂM': $danger_count++; break;
            case 'NGHIÊM TRỌNG': $serious_count++; break;
            case 'KHẨN CẤP': $urgent_count++; break;
            case 'CẦN XỬ LÝ': $need_action_count++; break;
        }
    }

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
                <h2><i class="fas fa-exclamation-triangle text-danger me-2"></i>Xe quá hạn bảo dưỡng</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Báo cáo</a></li>
                        <li class="breadcrumb-item active">Xe quá hạn</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-danger me-2">
                    <i class="fas fa-print me-2"></i>In danh sách khẩn cấp
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Cảnh báo nếu có xe quá hạn -->
        <?php if (!empty($overdue_data)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>CẢNH BÁO QUAN TRỌNG!</h5>
                <p class="mb-0">
                    Có <strong><?php echo count($overdue_data); ?> xe</strong> đã quá hạn bảo dưỡng.
                    Vui lòng liên hệ bộ phận kỹ thuật để lên lịch bảo dưỡng khẩn cấp.
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Thông báo -->
        <?php if ($error): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê mức độ nghiêm trọng -->
        <?php if (!empty($overdue_data)): ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-dark">
                        <div class="card-body">
                            <div class="text-dark mb-2">
                                <i class="fas fa-skull fa-2x"></i>
                            </div>
                            <h3 class="text-dark"><?php echo $danger_count; ?></h3>
                            <small class="text-danger fw-bold">NGUY HIỂM (>90 ngày)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-danger">
                        <div class="card-body">
                            <div class="text-danger mb-2">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <h3 class="text-danger"><?php echo $serious_count; ?></h3>
                            <small class="text-danger">NGHIÊM TRỌNG (30-90 ngày)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <div class="text-warning mb-2">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h3 class="text-warning"><?php echo $urgent_count; ?></h3>
                            <small class="text-warning">KHẨN CẤP (7-30 ngày)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <div class="text-info mb-2">
                                <i class="fas fa-wrench fa-2x"></i>
                            </div>
                            <h3 class="text-info"><?php echo $need_action_count; ?></h3>
                            <small class="text-info">CẦN XỬ LÝ (0-7 ngày)</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list-alt me-2"></i>
                    Danh sách xe quá hạn bảo dưỡng
                    <?php if (!empty($overdue_data)): ?>
                        <span class="badge bg-danger ms-2"><?php echo count($overdue_data); ?> xe</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($overdue_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5 class="text-success">Tuyệt vời!</h5>
                        <p class="text-muted">Hiện tại không có xe nào quá hạn bảo dưỡng</p>
                        <a href="maintenance_report.php" class="btn btn-primary">
                            <i class="fas fa-clipboard-check me-2"></i>Xem báo cáo đầy đủ
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="8%">Mã xe</th>
                                <th width="12%">Loại xe</th>
                                <th width="10%">Mức độ</th>
                                <th width="10%">Quá hạn</th>
                                <th width="10%">Hạn đăng kiểm</th>
                                <th width="8%">Hoạt động</th>
                                <th width="12%">Chuyến cuối</th>
                                <th width="10%">Khuyến nghị</th>
                                <th width="10%">Hành động</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($overdue_data as $row): ?>
                                <?php
                                // Xác định class cho dòng dựa trên mức độ nghiêm trọng
                                $row_class = '';
                                switch($row['MucDoNghiemTrong']) {
                                    case 'NGUY HIỂM': $row_class = 'table-dark'; break;
                                    case 'NGHIÊM TRỌNG': $row_class = 'table-danger'; break;
                                    case 'KHẨN CẤP': $row_class = 'table-warning'; break;
                                    default: $row_class = 'table-info'; break;
                                }
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($row['MaXe']); ?></span>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['TenLoaiXe']); ?></div>
                                        <small class="text-muted"><?php echo $row['SoGhe']; ?> ghế</small>
                                    </td>
                                    <td>
                                        <?php
                                        $severity_class = '';
                                        switch($row['MucDoNghiemTrong']) {
                                            case 'NGUY HIỂM': $severity_class = 'bg-dark text-white'; break;
                                            case 'NGHIÊM TRỌNG': $severity_class = 'bg-danger'; break;
                                            case 'KHẨN CẤP': $severity_class = 'bg-warning text-dark'; break;
                                            default: $severity_class = 'bg-info'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $severity_class; ?> small">
                                        <?php echo $row['MucDoNghiemTrong']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                        <span class="fw-bold text-danger fs-5">
                                            <?php echo $row['NgayQuaHan']; ?>
                                        </span>
                                            <br><small class="text-muted">ngày</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($row['SoNgayDKConLai'] < 0): ?>
                                            <span class="badge bg-danger small">Quá hạn ĐK</span>
                                            <br><small class="text-danger">
                                                <?php echo date('d/m/Y', strtotime($row['HanDangKiem'])); ?>
                                            </small>
                                        <?php elseif($row['SoNgayDKConLai'] <= 30): ?>
                                            <span class="badge bg-warning text-dark small">Sắp hết hạn ĐK</span>
                                            <br><small class="text-warning">
                                                <?php echo date('d/m/Y', strtotime($row['HanDangKiem'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-success small">Còn hạn ĐK</span>
                                            <br><small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($row['HanDangKiem'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                            <?php if($row['SoChuyenTuanNay'] > 0): ?>
                                                <span class="badge bg-danger">Đang hoạt động</span>
                                                <br><small class="text-danger fw-bold">DỪNG NGAY!</small>
                                            <?php elseif($row['SoChuyenThangNay'] > 0): ?>
                                                <span class="badge bg-warning text-dark">Hoạt động tháng này</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Không hoạt động</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($row['ChuyenCuoiCung']): ?>
                                            <div><?php echo date('d/m/Y', strtotime($row['ChuyenCuoiCung'])); ?></div>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($row['ChuyenCuoiCung'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có chuyến</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        switch($row['MucDoNghiemTrong']) {
                                            case 'NGUY HIỂM':
                                                echo '<span class="badge bg-dark text-white small">NGỪNG HOẠT ĐỘNG</span>';
                                                break;
                                            case 'NGHIÊM TRỌNG':
                                                echo '<span class="badge bg-danger small">BẢO DƯỠNG NGAY</span>';
                                                break;
                                            case 'KHẨN CẤP':
                                                echo '<span class="badge bg-warning text-dark small">LÊN LỊCH GẤP</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-info small">LIÊN HỆ KỸ THUẬT</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical" style="width: 100%;">
                                            <a href="../xe_khach/view.php?id=<?php echo $row['MaXe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết xe">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if($row['SoChuyenTuanNay'] > 0): ?>
                                                <button class="btn btn-sm btn-danger"
                                                        onclick="alert('Xe này đang hoạt động! Cần dừng hoạt động ngay lập tức để bảo dưỡng.')"
                                                        data-bs-toggle="tooltip" title="Xe đang hoạt động - Nguy hiểm!">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="tel:0123456789" class="btn btn-sm btn-success"
                                                   data-bs-toggle="tooltip" title="Gọi bộ phận kỹ thuật">
                                                    <i class="fas fa-phone"></i>
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

            <?php if (!empty($overdue_data)): ?>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Báo cáo được cập nhật lúc: <?php echo date('d/m/Y H:i:s'); ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Liên hệ bộ phận kỹ thuật: <strong>0123-456-789</strong>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
