<?php
$page_title = "Trang chủ - Quản lý xe khách";
$nav_path = "./";
$css_path = "./style.css";

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Thống kê tổng quan
$stats = [];

try {
    // Tổng số xe
    $query = "SELECT COUNT(*) as total FROM xe_khach";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['xe_khach'] = $stmt->fetchColumn();

    // Tổng số tuyến đường
    $query = "SELECT COUNT(*) as total FROM tuyen_duong";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['tuyen_duong'] = $stmt->fetchColumn();

    // Tổng số tài xế
    $query = "SELECT COUNT(*) as total FROM tai_xe";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['tai_xe'] = $stmt->fetchColumn();

    // Số chuyến xe hôm nay
    $query = "SELECT COUNT(*) as total FROM chuyen_xe WHERE DATE(GioDi) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['chuyen_xe_today'] = $stmt->fetchColumn();

    // Số vé đã bán hôm nay
    $query = "SELECT COUNT(*) as total FROM ve_xe vx 
              JOIN chuyen_xe cx ON vx.MaChuyenXe = cx.MaChuyenXe 
              WHERE DATE(cx.GioDi) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['ve_xe_today'] = $stmt->fetchColumn();

} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="jumbotron bg-primary text-white p-5 rounded">
            <h1 class="display-4">
                <i class="fas fa-bus me-3"></i>
                Hệ thống quản lý xe khách
            </h1>
            <p class="lead">Quản lý toàn diện hoạt động vận tải hành khách</p>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.3);">
            <p>Hệ thống cung cấp các chức năng quản lý xe, tuyến đường, tài xế, chuyến xe và bán vé một cách hiệu quả.</p>
        </div>
    </div>
</div>

<!-- Thống kê tổng quan -->
<div class="row mb-5">
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-primary mb-2">
                    <i class="fas fa-bus fa-2x"></i>
                </div>
                <h3 class="card-title text-primary"><?php echo $stats['xe_khach'] ?? 0; ?></h3>
                <p class="card-text text-muted">Xe khách</p>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-success mb-2">
                    <i class="fas fa-route fa-2x"></i>
                </div>
                <h3 class="card-title text-success"><?php echo $stats['tuyen_duong'] ?? 0; ?></h3>
                <p class="card-text text-muted">Tuyến đường</p>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-info mb-2">
                    <i class="fas fa-user-tie fa-2x"></i>
                </div>
                <h3 class="card-title text-info"><?php echo $stats['tai_xe'] ?? 0; ?></h3>
                <p class="card-text text-muted">Tài xế</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-warning mb-2">
                    <i class="fas fa-calendar-day fa-2x"></i>
                </div>
                <h3 class="card-title text-warning"><?php echo $stats['chuyen_xe_today'] ?? 0; ?></h3>
                <p class="card-text text-muted">Chuyến hôm nay</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-danger mb-2">
                    <i class="fas fa-ticket-alt fa-2x"></i>
                </div>
                <h3 class="card-title text-danger"><?php echo $stats['ve_xe_today'] ?? 0; ?></h3>
                <p class="card-text text-muted">Vé bán hôm nay</p>
            </div>
        </div>
    </div>
</div>

<!-- Menu chức năng -->
<div class="row">
    <div class="col-12 mb-4">
        <h3><i class="fas fa-th-large me-2"></i>Chức năng chính</h3>
    </div>

    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-primary mb-3">
                    <i class="fas fa-cogs fa-3x"></i>
                </div>
                <h5 class="card-title">Quản lý cơ bản</h5>
                <p class="card-text text-muted">Quản lý loại xe, xe khách, tuyến đường, tài xế</p>
                <div class="d-grid gap-2">
                    <a href="loai_xe/index.php" class="btn btn-outline-primary btn-sm">Loại xe</a>
                    <a href="xe_khach/index.php" class="btn btn-outline-primary btn-sm">Xe khách</a>
                    <a href="tuyen_duong/index.php" class="btn btn-outline-primary btn-sm">Tuyến đường</a>
                    <a href="tai_xe/index.php" class="btn btn-outline-primary btn-sm">Tài xế</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-success mb-3">
                    <i class="fas fa-dollar-sign fa-3x"></i>
                </div>
                <h5 class="card-title">Quản lý giá cả</h5>
                <p class="card-text text-muted">Thiết lập bảng giá theo tuyến đường và loại xe</p>
                <div class="d-grid">
                    <a href="bang_gia/index.php" class="btn btn-success">
                        <i class="fas fa-tags me-2"></i>Bảng giá
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-warning mb-3">
                    <i class="fas fa-route fa-3x"></i>
                </div>
                <h5 class="card-title">Quản lý chuyến</h5>
                <p class="card-text text-muted">Lập lịch chuyến xe và phân công tài xế</p>
                <div class="d-grid gap-2">
                    <a href="chuyen_xe/index.php" class="btn btn-outline-warning btn-sm">Chuyến xe</a>
                    <a href="phan_cong/index.php" class="btn btn-outline-warning btn-sm">Phân công</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-danger mb-3">
                    <i class="fas fa-ticket-alt fa-3x"></i>
                </div>
                <h5 class="card-title">Bán vé</h5>
                <p class="card-text text-muted">Quản lý vé xe và thông tin hành khách</p>
                <div class="d-grid">
                    <a href="ve_xe/index.php" class="btn btn-danger">
                        <i class="fas fa-plus me-2"></i>Quản lý vé
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
