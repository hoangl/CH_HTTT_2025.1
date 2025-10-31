<?php
$page_title = "Báo cáo doanh thu theo xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy năm và tháng từ form
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Lấy danh sách năm có dữ liệu
try {
    $years_query = "SELECT DISTINCT YEAR(cx.GioDi) as nam 
                   FROM chuyen_xe cx 
                   ORDER BY nam DESC";
    $years_stmt = $db->prepare($years_query);
    $years_stmt->execute();
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($available_years)) {
        $available_years = [date('Y')];
    }
} catch(PDOException $e) {
    $available_years = [date('Y')];
}

// Lấy dữ liệu báo cáo doanh thu xe
$revenue_data = [];
try {
    $query = "SELECT
                cx.MaXe,
                lx.TenLoaiXe,
                lx.SoGhe,
                COUNT(DISTINCT cx.MaChuyenXe) AS SoChuyen,
                COUNT(vx.MaVe) AS SoVeBan,
                COALESCE(SUM(vx.GiaVeThucTe), 0) AS DoanhThu,
                AVG(vx.GiaVeThucTe) AS GiaVeTrungBinh,
                (lx.SoGhe - 2) * COUNT(DISTINCT cx.MaChuyenXe) AS TongSoGheCoThe,
                ROUND((COUNT(vx.MaVe) / ((lx.SoGhe - 2) * COUNT(DISTINCT cx.MaChuyenXe)) * 100), 2) AS TyLeLapDay
              FROM chuyen_xe cx
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
              WHERE MONTH(cx.GioDi) = ? AND YEAR(cx.GioDi) = ?
              GROUP BY cx.MaXe, lx.TenLoaiXe, lx.SoGhe
              ORDER BY DoanhThu DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$selected_month, $selected_year]);
    $revenue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính tổng
    $total_revenue = array_sum(array_column($revenue_data, 'DoanhThu'));
    $total_trips = array_sum(array_column($revenue_data, 'SoChuyen'));
    $total_tickets = array_sum(array_column($revenue_data, 'SoVeBan'));

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
                <h2><i class="fas fa-bus text-primary me-2"></i>Báo cáo doanh thu theo xe</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Báo cáo</a></li>
                        <li class="breadcrumb-item active">Doanh thu xe</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary me-2">
                    <i class="fas fa-print me-2"></i>In báo cáo
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Năm</label>
                        <select class="form-select" name="year">
                            <?php foreach($available_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo ($year == $selected_year) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar-alt me-1"></i>Tháng</label>
                        <select class="form-select" name="month">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == $selected_month) ? 'selected' : ''; ?>>
                                    Tháng <?php echo $m; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Xem báo cáo
                        </button>
                    </div>
                    <div class="col-md-3 text-end">
                        <small class="text-muted">
                            Báo cáo tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?>
                        </small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tổng quan -->
        <?php if (!empty($revenue_data)): ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <div class="text-success mb-2">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                            <h4 class="text-success"><?php echo number_format($total_revenue, 0, ',', '.'); ?>đ</h4>
                            <small class="text-muted">Tổng doanh thu</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <div class="text-primary mb-2">
                                <i class="fas fa-bus fa-2x"></i>
                            </div>
                            <h4 class="text-primary"><?php echo count($revenue_data); ?></h4>
                            <small class="text-muted">Xe hoạt động</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <div class="text-info mb-2">
                                <i class="fas fa-route fa-2x"></i>
                            </div>
                            <h4 class="text-info"><?php echo number_format($total_trips); ?></h4>
                            <small class="text-muted">Tổng chuyến</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <div class="text-warning mb-2">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                            <h4 class="text-warning"><?php echo number_format($total_tickets); ?></h4>
                            <small class="text-muted">Vé đã bán</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Doanh thu xe tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($revenue_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có dữ liệu</h5>
                        <p class="text-muted">Không có xe nào hoạt động trong tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="12%">Mã xe</th>
                                <th width="15%">Loại xe</th>
                                <th width="8%">Số chuyến</th>
                                <th width="8%">Vé bán</th>
                                <th width="15%">Doanh thu</th>
                                <th width="10%">Giá TB</th>
                                <th width="8%">Tỷ lệ lấp đầy</th>
                                <th width="7%">Hiệu suất</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($revenue_data as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($row['MaXe']); ?></span>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['TenLoaiXe']); ?></div>
                                        <small class="text-muted"><?php echo $row['SoGhe']; ?> ghế</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo number_format($row['SoChuyen']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo number_format($row['SoVeBan']); ?></span>
                                    </td>
                                    <td>
                                    <span class="fw-bold text-success">
                                        <?php echo number_format($row['DoanhThu'], 0, ',', '.'); ?>đ
                                    </span>
                                    </td>
                                    <td>
                                        <small><?php echo $row['GiaVeTrungBinh'] ? number_format($row['GiaVeTrungBinh'], 0, ',', '.') . 'đ' : 'N/A'; ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $fill_rate = floatval($row['TyLeLapDay']);
                                        $badge_class = $fill_rate >= 80 ? 'bg-success' : ($fill_rate >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo number_format($fill_rate, 1); ?>%
                                    </span>
                                    </td>
                                    <td>
                                        <?php
                                        $efficiency = $row['SoChuyen'] > 0 ? ($row['DoanhThu'] / $row['SoChuyen']) : 0;
                                        $efficiency_class = $efficiency >= 1000000 ? 'text-success' : ($efficiency >= 500000 ? 'text-warning' : 'text-danger');
                                        ?>
                                        <small class="<?php echo $efficiency_class; ?>">
                                            <?php echo number_format($efficiency / 1000, 0); ?>K/chuyến
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Tổng cộng:</th>
                                <th class="text-info">
                                    <?php echo number_format($total_trips); ?>
                                </th>
                                <th class="text-success">
                                    <?php echo number_format($total_tickets); ?>
                                </th>
                                <th class="text-success">
                                    <?php echo number_format($total_revenue, 0, ',', '.'); ?>đ
                                </th>
                                <th colspan="3" class="text-muted">
                                    <?php echo count($revenue_data); ?> xe
                                </th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
