<?php
$page_title = "Báo cáo lương tháng tài xế";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy năm và tháng từ form hoặc mặc định tháng hiện tại
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Lấy danh sách năm có dữ liệu
try {
    $years_query = "SELECT DISTINCT YEAR(cx.GioDi) as nam 
                   FROM chuyen_xe cx 
                   WHERE cx.TrangThai = 2
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

// Lấy dữ liệu báo cáo lương
$salary_data = [];
try {
    $query = "SELECT 
                tx.MaTaiXe,
                tx.HoTen,
                tx.SDT,
                SUM(pc.ThuLao) AS LuongThang,
                COUNT(pc.MaChuyenXe) AS SoChuyen,
                AVG(pc.ThuLao) AS LuongTrungBinh,
                SUM(CASE WHEN pc.VaiTro = 1 THEN pc.ThuLao ELSE 0 END) AS LuongTaiXeChinh,
                SUM(CASE WHEN pc.VaiTro = 2 THEN pc.ThuLao ELSE 0 END) AS LuongLaiPhu,
                COUNT(CASE WHEN pc.VaiTro = 1 THEN 1 END) AS SoChuyenChinh,
                COUNT(CASE WHEN pc.VaiTro = 2 THEN 1 END) AS SoChuyenPhu
              FROM tai_xe tx
              LEFT JOIN phan_cong pc ON tx.MaTaiXe = pc.MaTaiXe
              LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
              WHERE cx.TrangThai = 2 
                AND MONTH(cx.GioDi) = ? 
                AND YEAR(cx.GioDi) = ?
              GROUP BY tx.MaTaiXe, tx.HoTen, tx.SDT
              HAVING LuongThang > 0
              ORDER BY LuongThang DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$selected_month, $selected_year]);
    $salary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính tổng
    $total_salary = array_sum(array_column($salary_data, 'LuongThang'));
    $total_trips = array_sum(array_column($salary_data, 'SoChuyen'));

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
                <h2><i class="fas fa-money-bill-wave text-success me-2"></i>Báo cáo lương tháng tài xế</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Báo cáo</a></li>
                        <li class="breadcrumb-item active">Lương tháng</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-success me-2">
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

        <!-- Thông báo -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Tổng quan -->
        <?php if (!empty($salary_data)): ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <div class="text-success mb-2">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                            <h4 class="text-success"><?php echo number_format($total_salary, 0, ',', '.'); ?>đ</h4>
                            <small class="text-muted">Tổng lương</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <div class="text-primary mb-2">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h4 class="text-primary"><?php echo count($salary_data); ?></h4>
                            <small class="text-muted">Tài xế có lương</small>
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
                                <i class="fas fa-calculator fa-2x"></i>
                            </div>
                            <h4 class="text-warning"><?php echo $total_trips > 0 ? number_format($total_salary / $total_trips, 0, ',', '.') : '0'; ?>đ</h4>
                            <small class="text-muted">Lương TB/chuyến</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Bảng lương tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($salary_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có dữ liệu</h5>
                        <p class="text-muted">Không có tài xế nào có lương trong tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Mã tài xế</th>
                                <th width="20%">Họ tên</th>
                                <th width="12%">SĐT</th>
                                <th width="12%">Tổng lương</th>
                                <th width="8%">Số chuyến</th>
                                <th width="12%">Lương TB</th>
                                <th width="8%">Chính</th>
                                <th width="8%">Phụ</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($salary_data as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($row['MaTaiXe']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['HoTen']); ?></div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['SDT']); ?></small>
                                    </td>
                                    <td>
                                    <span class="fw-bold text-success">
                                        <?php echo number_format($row['LuongThang'], 0, ',', '.'); ?>đ
                                    </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $row['SoChuyen']; ?></span>
                                    </td>
                                    <td>
                                        <small><?php echo number_format($row['LuongTrungBinh'], 0, ',', '.'); ?>đ</small>
                                    </td>
                                    <td>
                                        <?php if($row['SoChuyenChinh'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $row['SoChuyenChinh']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['SoChuyenPhu'] > 0): ?>
                                            <span class="badge bg-secondary"><?php echo $row['SoChuyenPhu']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Tổng cộng:</th>
                                <th class="text-success">
                                    <?php echo number_format($total_salary, 0, ',', '.'); ?>đ
                                </th>
                                <th class="text-info">
                                    <?php echo number_format($total_trips); ?>
                                </th>
                                <th colspan="3" class="text-muted">
                                    <?php echo count($salary_data); ?> tài xế
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
