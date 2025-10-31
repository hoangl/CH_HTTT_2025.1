<?php
$page_title = "Báo cáo doanh thu theo tuyến đường";
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

// Lấy dữ liệu báo cáo doanh thu tuyến
$route_data = [];
try {
    $query = "SELECT 
                td.MaTuyenDuong,
                td.DiemDau,
                td.DiemCuoi,
                td.DoDai,
                td.DoPhucTap,
                CASE td.DoPhucTap
                    WHEN 1 THEN 'Đơn giản'
                    WHEN 2 THEN 'Trung bình'
                    WHEN 3 THEN 'Phức tạp'
                    ELSE 'Không xác định'
                END as TenDoPhucTap,
                COUNT(DISTINCT cx.MaChuyenXe) AS SoChuyen,
                COUNT(vx.MaVe) AS SoVeBan,
                COALESCE(SUM(vx.GiaVeThucTe), 0) AS DoanhThuTuyen,
                AVG(vx.GiaVeThucTe) AS GiaVeTrungBinh,
                ROUND(COALESCE(SUM(vx.GiaVeThucTe), 0) / td.DoDai, 0) AS DoanhThuPerKm,
                ROUND(COUNT(vx.MaVe) / COUNT(DISTINCT cx.MaChuyenXe), 2) AS VeTrungBinhMoiChuyen
              FROM tuyen_duong td
              LEFT JOIN chuyen_xe cx ON td.MaTuyenDuong = cx.MaTuyenDuong
              LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
              WHERE cx.MaChuyenXe IS NOT NULL
                AND MONTH(cx.GioDi) = ? AND YEAR(cx.GioDi) = ?
              GROUP BY td.MaTuyenDuong, td.DiemDau, td.DiemCuoi, td.DoDai, td.DoPhucTap
              ORDER BY DoanhThuTuyen DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$selected_month, $selected_year]);
    $route_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính tổng
    $total_revenue = array_sum(array_column($route_data, 'DoanhThuTuyen'));
    $total_trips = array_sum(array_column($route_data, 'SoChuyen'));
    $total_tickets = array_sum(array_column($route_data, 'SoVeBan'));
    $total_distance = array_sum(array_column($route_data, 'DoDai'));

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
                <h2><i class="fas fa-route text-info me-2"></i>Báo cáo doanh thu theo tuyến đường</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Báo cáo</a></li>
                        <li class="breadcrumb-item active">Doanh thu tuyến</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-info me-2">
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
                        <button type="submit" class="btn btn-info">
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
        <?php if (!empty($route_data)): ?>
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
                                <i class="fas fa-map fa-2x"></i>
                            </div>
                            <h4 class="text-primary"><?php echo count($route_data); ?></h4>
                            <small class="text-muted">Tuyến hoạt động</small>
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
                    <i class="fas fa-chart-pie me-2"></i>
                    Doanh thu tuyến tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($route_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có dữ liệu</h5>
                        <p class="text-muted">Không có tuyến nào hoạt động trong tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Mã tuyến</th>
                                <th width="25%">Tuyến đường</th>
                                <th width="8%">Khoảng cách</th>
                                <th width="8%">Độ khó</th>
                                <th width="8%">Số chuyến</th>
                                <th width="8%">Vé bán</th>
                                <th width="15%">Doanh thu</th>
                                <th width="8%">Vé TB/chuyến</th>
                                <th width="5%">Đ.thu/km</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($route_data as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($row['MaTuyenDuong']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['DiemDau']); ?></div>
                                                <small class="text-primary">
                                                    <i class="fas fa-arrow-down me-1"></i>
                                                </small>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['DiemCuoi']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo number_format($row['DoDai'], 1); ?>km</span>
                                    </td>
                                    <td>
                                        <?php
                                        $complexity_class = '';
                                        switch($row['DoPhucTap']) {
                                            case 1: $complexity_class = 'bg-success'; break;
                                            case 2: $complexity_class = 'bg-warning text-dark'; break;
                                            case 3: $complexity_class = 'bg-danger'; break;
                                            default: $complexity_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $complexity_class; ?> small">
                                        <?php echo $row['TenDoPhucTap']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo number_format($row['SoChuyen']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo number_format($row['SoVeBan']); ?></span>
                                    </td>
                                    <td>
                                    <span class="fw-bold text-success">
                                        <?php echo number_format($row['DoanhThuTuyen'], 0, ',', '.'); ?>đ
                                    </span>
                                    </td>
                                    <td>
                                        <small class="text-info">
                                            <?php echo number_format($row['VeTrungBinhMoiChuyen'], 1); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-warning">
                                            <?php echo number_format($row['DoanhThuPerKm'], 0); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Tổng cộng:</th>
                                <th class="text-primary">
                                    <?php echo number_format($total_trips); ?>
                                </th>
                                <th class="text-success">
                                    <?php echo number_format($total_tickets); ?>
                                </th>
                                <th class="text-success">
                                    <?php echo number_format($total_revenue, 0, ',', '.'); ?>đ
                                </th>
                                <th colspan="2" class="text-muted">
                                    <?php echo count($route_data); ?> tuyến
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
