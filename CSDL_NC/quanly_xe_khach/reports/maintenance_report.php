<?php
$page_title = "Báo cáo tình trạng bảo dưỡng";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy dữ liệu báo cáo bảo dưỡng
$maintenance_data = [];
try {
    $query = "SELECT 
                xk.MaXe,
                lx.TenLoaiXe,
                lx.SoGhe,
                xk.SoNgayBDConLai,
                xk.HanDangKiem,
                CASE 
                    WHEN xk.SoNgayBDConLai <= 0 THEN 'CẦN BẢO DƯỠNG GẤP'
                    WHEN xk.SoNgayBDConLai <= 7 THEN 'Cần bảo dưỡng trong tuần'
                    WHEN xk.SoNgayBDConLai <= 30 THEN 'Sắp tới hạn bảo dưỡng'
                    ELSE 'Bình thường'
                END AS TinhTrangBaoDuong,
                CASE
                    WHEN xk.HanDangKiem < CURDATE() THEN 'QUÁ HẠN ĐĂNG KIỂM'
                    WHEN xk.HanDangKiem <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Sắp hết hạn đăng kiểm'
                    ELSE 'Còn hạn đăng kiểm'
                END AS TinhTrangDangKiem,
                DATEDIFF(xk.HanDangKiem, CURDATE()) AS SoNgayDKConLai,
                -- Thống kê hoạt động gần đây
                COUNT(cx.MaChuyenXe) AS SoChuyenThangNay,
                COALESCE(SUM(CASE WHEN cx.TrangThai = 2 THEN 1 ELSE 0 END), 0) AS SoChuyenHoanThanh
              FROM xe_khach xk
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN chuyen_xe cx ON (xk.MaXe = cx.MaXe 
                                       AND MONTH(cx.GioDi) = MONTH(CURDATE()) 
                                       AND YEAR(cx.GioDi) = YEAR(CURDATE()))
              GROUP BY xk.MaXe, lx.TenLoaiXe, lx.SoGhe, xk.SoNgayBDConLai, xk.HanDangKiem
              ORDER BY xk.SoNgayBDConLai ASC, xk.HanDangKiem ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $maintenance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê tổng quan
    $overdue_maintenance = 0;
    $due_soon_maintenance = 0;
    $overdue_inspection = 0;
    $due_soon_inspection = 0;

    foreach($maintenance_data as $row) {
        if($row['SoNgayBDConLai'] <= 0) $overdue_maintenance++;
        elseif($row['SoNgayBDConLai'] <= 30) $due_soon_maintenance++;

        if($row['SoNgayDKConLai'] < 0) $overdue_inspection++;
        elseif($row['SoNgayDKConLai'] <= 30) $due_soon_inspection++;
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
                <h2><i class="fas fa-tools text-warning me-2"></i>Báo cáo tình trạng bảo dưỡng</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Báo cáo</a></li>
                        <li class="breadcrumb-item active">Tình trạng bảo dưỡng</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-warning me-2">
                    <i class="fas fa-print me-2"></i>In báo cáo
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Thông báo -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Tổng quan cảnh báo -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <div class="text-danger mb-2">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <h3 class="text-danger"><?php echo number_format($overdue_maintenance); ?></h3>
                        <small class="text-muted">Quá hạn bảo dưỡng</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <div class="text-warning mb-2">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <h3 class="text-warning"><?php echo number_format($due_soon_maintenance); ?></h3>
                        <small class="text-muted">Sắp hết hạn BD</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <div class="text-danger mb-2">
                            <i class="fas fa-certificate fa-2x"></i>
                        </div>
                        <h3 class="text-danger"><?php echo number_format($overdue_inspection); ?></h3>
                        <small class="text-muted">Quá hạn ĐK</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <div class="text-info mb-2">
                            <i class="fas fa-bus fa-2x"></i>
                        </div>
                        <h3 class="text-info"><?php echo count($maintenance_data); ?></h3>
                        <small class="text-muted">Tổng số xe</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc nhanh -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-filter="all">
                            <i class="fas fa-list me-1"></i>Tất cả
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-danger btn-sm filter-btn" data-filter="overdue_maintenance">
                            <i class="fas fa-exclamation-triangle me-1"></i>Quá hạn BD
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-warning btn-sm filter-btn" data-filter="due_soon_maintenance">
                            <i class="fas fa-clock me-1"></i>Sắp hết hạn BD
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-danger btn-sm filter-btn" data-filter="overdue_inspection">
                            <i class="fas fa-certificate me-1"></i>Quá hạn ĐK
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-success btn-sm filter-btn" data-filter="normal">
                            <i class="fas fa-check me-1"></i>Bình thường
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-info btn-sm filter-btn" data-filter="active">
                            <i class="fas fa-play me-1"></i>Đang hoạt động
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Chi tiết tình trạng bảo dưỡng và đăng kiểm
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($maintenance_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có dữ liệu</h5>
                        <p class="text-muted">Không có thông tin xe khách nào trong hệ thống</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="maintenanceTable">
                            <thead class="table-dark">
                            <tr>
                                <th width="8%">Mã xe</th>
                                <th width="15%">Loại xe</th>
                                <th width="12%">Tình trạng BD</th>
                                <th width="8%">Ngày BD còn lại</th>
                                <th width="12%">Tình trạng ĐK</th>
                                <th width="10%">Hạn đăng kiểm</th>
                                <th width="8%">Hoạt động</th>
                                <th width="8%">Chuyến/tháng</th>
                                <th width="9%">Ưu tiên</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($maintenance_data as $row): ?>
                                <?php
                                // Xác định class cho dòng
                                $row_class = '';
                                $priority = 0;

                                if($row['SoNgayBDConLai'] <= 0) {
                                    $row_class = 'table-danger';
                                    $priority += 100;
                                } elseif($row['SoNgayBDConLai'] <= 7) {
                                    $row_class = 'table-warning';
                                    $priority += 50;
                                }

                                if($row['SoNgayDKConLai'] < 0) {
                                    $row_class = 'table-danger';
                                    $priority += 200;
                                } elseif($row['SoNgayDKConLai'] <= 30) {
                                    if(!$row_class) $row_class = 'table-info';
                                    $priority += 25;
                                }
                                ?>
                                <tr class="<?php echo $row_class; ?>"
                                    data-maintenance="<?php echo $row['SoNgayBDConLai'] <= 0 ? 'overdue' : ($row['SoNgayBDConLai'] <= 30 ? 'due_soon' : 'normal'); ?>"
                                    data-inspection="<?php echo $row['SoNgayDKConLai'] < 0 ? 'overdue' : ($row['SoNgayDKConLai'] <= 30 ? 'due_soon' : 'normal'); ?>"
                                    data-activity="<?php echo $row['SoChuyenThangNay'] > 0 ? 'active' : 'inactive'; ?>"
                                    data-priority="<?php echo $priority; ?>">
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($row['MaXe']); ?></span>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['TenLoaiXe']); ?></div>
                                        <small class="text-muted"><?php echo $row['SoGhe']; ?> ghế</small>
                                    </td>
                                    <td>
                                        <?php
                                        $bd_class = '';
                                        if($row['SoNgayBDConLai'] <= 0) $bd_class = 'bg-danger';
                                        elseif($row['SoNgayBDConLai'] <= 7) $bd_class = 'bg-warning text-dark';
                                        elseif($row['SoNgayBDConLai'] <= 30) $bd_class = 'bg-info';
                                        else $bd_class = 'bg-success';
                                        ?>
                                        <span class="badge <?php echo $bd_class; ?> small">
                                        <?php echo $row['TinhTrangBaoDuong']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <?php if($row['SoNgayBDConLai'] <= 0): ?>
                                            <span class="fw-bold text-danger">
                                            <?php echo abs($row['SoNgayBDConLai']); ?> ngày
                                        </span>
                                            <br><small class="text-danger">Quá hạn</small>
                                        <?php else: ?>
                                            <span class="fw-bold">
                                            <?php echo $row['SoNgayBDConLai']; ?> ngày
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $dk_class = '';
                                        if($row['SoNgayDKConLai'] < 0) $dk_class = 'bg-danger';
                                        elseif($row['SoNgayDKConLai'] <= 30) $dk_class = 'bg-warning text-dark';
                                        else $dk_class = 'bg-success';
                                        ?>
                                        <span class="badge <?php echo $dk_class; ?> small">
                                        <?php echo $row['TinhTrangDangKiem']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <div><?php echo date('d/m/Y', strtotime($row['HanDangKiem'])); ?></div>
                                        <?php if($row['SoNgayDKConLai'] < 0): ?>
                                            <small class="text-danger">
                                                Quá <?php echo abs($row['SoNgayDKConLai']); ?> ngày
                                            </small>
                                        <?php elseif($row['SoNgayDKConLai'] <= 30): ?>
                                            <small class="text-warning">
                                                Còn <?php echo $row['SoNgayDKConLai']; ?> ngày
                                            </small>
                                        <?php else: ?>
                                            <small class="text-success">
                                                Còn <?php echo $row['SoNgayDKConLai']; ?> ngày
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['SoChuyenThangNay'] > 0): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Không hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="fw-bold"><?php echo $row['SoChuyenThangNay']; ?></span> chuyến
                                        </div>
                                        <small class="text-muted">
                                            (<?php echo $row['SoChuyenHoanThanh']; ?> hoàn thành)
                                        </small>
                                    </td>
                                    <td>
                                        <?php if($priority >= 200): ?>
                                            <span class="badge bg-danger">Khẩn cấp</span>
                                        <?php elseif($priority >= 100): ?>
                                            <span class="badge bg-warning text-dark">Cao</span>
                                        <?php elseif($priority >= 25): ?>
                                            <span class="badge bg-info">Trung bình</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Thấp</span>
                                        <?php endif; ?>
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

<script>
    // Bộ lọc
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            const rows = document.querySelectorAll('#maintenanceTable tbody tr');

            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter rows
            rows.forEach(row => {
                const maintenance = row.getAttribute('data-maintenance');
                const inspection = row.getAttribute('data-inspection');
                const activity = row.getAttribute('data-activity');

                let show = false;

                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'overdue_maintenance':
                        show = maintenance === 'overdue';
                        break;
                    case 'due_soon_maintenance':
                        show = maintenance === 'due_soon';
                        break;
                    case 'overdue_inspection':
                        show = inspection === 'overdue';
                        break;
                    case 'normal':
                        show = maintenance === 'normal' && inspection === 'normal';
                        break;
                    case 'active':
                        show = activity === 'active';
                        break;
                }

                row.style.display = show ? '' : 'none';
            });
        });
    });

    // Set default active
    document.querySelector('.filter-btn[data-filter="all"]').classList.add('active');
</script>

<style>
    .filter-btn.active {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }
</style>

<?php include '../includes/footer.php'; ?>
