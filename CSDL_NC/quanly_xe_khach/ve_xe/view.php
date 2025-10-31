<?php
$page_title = "Chi tiết vé xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin vé xe chi tiết
try {
    $query = "SELECT vx.*, 
                     cx.GioDi, cx.GioDen, cx.TrangThai,
                     td.DiemDau, td.DiemCuoi, td.DoDai, td.DoPhucTap,
                     xk.MaXe, lx.TenLoaiXe, lx.SoGhe,
                     bg.GiaVeNiemYet,
                     CASE cx.TrangThai 
                         WHEN 1 THEN 'Chờ khởi hành'
                         WHEN 2 THEN 'Hoàn thành'
                         ELSE 'Không xác định'
                     END as TenTrangThai,
                     CASE 
                         WHEN cx.GioDi <= NOW() AND cx.TrangThai = 1 THEN 'Đang di chuyển'
                         WHEN cx.GioDi > NOW() AND cx.TrangThai = 1 THEN 'Chờ khởi hành'
                         WHEN cx.TrangThai = 2 THEN 'Đã hoàn thành'
                         ELSE 'Bình thường'
                     END as TinhTrang
              FROM ve_xe vx
              LEFT JOIN chuyen_xe cx ON vx.MaChuyenXe = cx.MaChuyenXe
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN bang_gia bg ON (cx.MaTuyenDuong = bg.MaTuyenDuong 
                                       AND xk.MaLoaiXe = bg.MaLoaiXe 
                                       AND bg.NgayBatDau <= cx.GioDi 
                                       AND (bg.NgayKetThuc IS NULL OR bg.NgayKetThuc >= cx.GioDi))
              WHERE vx.MaVe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $ve_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ve_xe) {
        header("Location: index.php");
        exit();
    }

    // Lấy thông tin tài xế của chuyến xe
    $query_tai_xe = "SELECT pc.*, tx.HoTen, tx.SDT,
                            CASE pc.VaiTro
                                WHEN 1 THEN 'Tài xế chính'
                                WHEN 2 THEN 'Lái phụ'
                                ELSE 'Không xác định'
                            END as TenVaiTro
                     FROM phan_cong pc
                     LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
                     WHERE pc.MaChuyenXe = ?
                     ORDER BY pc.VaiTro";
    $stmt_tai_xe = $db->prepare($query_tai_xe);
    $stmt_tai_xe->execute([$ve_xe['MaChuyenXe']]);
    $tai_xe_list = $stmt_tai_xe->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách vé khác trong cùng chuyến
    $query_ve_khac = "SELECT vx.* FROM ve_xe vx 
                      WHERE vx.MaChuyenXe = ? AND vx.MaVe != ? 
                      ORDER BY vx.ViTri";
    $stmt_ve_khac = $db->prepare($query_ve_khac);
    $stmt_ve_khac->execute([$ve_xe['MaChuyenXe'], $id]);
    $ve_khac = $stmt_ve_khac->fetchAll(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết vé xe</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Vé xe</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($ve_xe['MaVe']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <?php if ($ve_xe['TrangThai'] == 1 && strtotime($ve_xe['GioDi']) > time()): ?>
                    <a href="edit.php?id=<?php echo $ve_xe['MaVe']; ?>" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-success me-2">
                    <i class="fas fa-print me-2"></i>In vé
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Thông tin vé -->
            <div class="col-lg-5 mb-4">
                <div class="card h-100 shadow-sm" id="ticket-card">
                    <div class="card-header bg-primary text-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-ticket-alt me-2"></i>VÉ XE KHÁCH
                                </h5>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($ve_xe['MaVe']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Thông tin hành khách -->
                        <div class="text-center mb-4">
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <h4 class="text-dark"><?php echo htmlspecialchars($ve_xe['TenHanhKhach']); ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($ve_xe['SDTHanhKhach']); ?>
                            </p>
                        </div>

                        <!-- Thông tin tuyến đường -->
                        <div class="mb-4">
                            <div class="row text-center">
                                <div class="col-5">
                                    <div class="p-3 bg-light rounded">
                                        <div class="text-success mb-2">
                                            <i class="fas fa-map-marker-alt fa-2x"></i>
                                        </div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($ve_xe['DiemDau']); ?></h6>
                                        <small class="text-muted">Điểm đi</small>
                                    </div>
                                </div>
                                <div class="col-2 d-flex align-items-center justify-content-center">
                                    <div class="text-primary">
                                        <i class="fas fa-arrow-right fa-2x"></i>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="p-3 bg-light rounded">
                                        <div class="text-danger mb-2">
                                            <i class="fas fa-map-marker-alt fa-2x"></i>
                                        </div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($ve_xe['DiemCuoi']); ?></h6>
                                        <small class="text-muted">Điểm đến</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chi tiết vé -->
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Chuyến xe:</td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($ve_xe['MaChuyenXe']); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Xe:</td>
                                <td>
                                    <?php echo htmlspecialchars($ve_xe['MaXe']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($ve_xe['TenLoaiXe']); ?></small>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Vị trí ghế:</td>
                                <td><span class="badge bg-warning text-dark fs-5"><?php echo htmlspecialchars($ve_xe['ViTri']); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Khoảng cách:</td>
                                <td><?php echo number_format($ve_xe['DoDai'], 1); ?> km</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Giờ đi:</td>
                                <td class="fw-bold text-success"><?php echo date('d/m/Y H:i', strtotime($ve_xe['GioDi'])); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Giờ đến (DK):</td>
                                <td class="fw-bold text-danger"><?php echo date('d/m/Y H:i', strtotime($ve_xe['GioDen'])); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Giá vé:</td>
                                <td>
                                    <span class="fw-bold text-success fs-5">
                                        <?php echo number_format($ve_xe['GiaVeThucTe'], 0, ',', '.'); ?>đ
                                    </span>
                                    <?php if ($ve_xe['GiaVeNiemYet'] && $ve_xe['GiaVeNiemYet'] != $ve_xe['GiaVeThucTe']): ?>
                                        <br><small class="text-muted">
                                            Niêm yết: <?php echo number_format($ve_xe['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>

                        <!-- Trạng thái -->
                        <div class="text-center mt-4">
                            <?php
                            $trang_thai_class = '';
                            switch($ve_xe['TinhTrang']) {
                                case 'Chờ khởi hành': $trang_thai_class = 'bg-warning text-dark'; break;
                                case 'Đang di chuyển': $trang_thai_class = 'bg-info'; break;
                                case 'Đã hoàn thành': $trang_thai_class = 'bg-success'; break;
                                default: $trang_thai_class = 'bg-secondary'; break;
                            }
                            ?>
                            <span class="badge <?php echo $trang_thai_class; ?> fs-6 px-4 py-2">
                                <?php echo $ve_xe['TinhTrang']; ?>
                            </span>
                        </div>

                        <!-- QR Code hoặc barcode có thể thêm vào đây -->
                        <div class="text-center mt-3">
                            <div class="bg-light p-3 rounded">
                                <div style="font-family: monospace; font-size: 12px; letter-spacing: 2px;">
                                    ||||| <?php echo $ve_xe['MaVe']; ?> |||||
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-light">
                        <small class="text-muted">
                            Vui lòng có mặt tại bến xe trước giờ khởi hành 15 phút<br>
                            Hotline: 1900 1234 | Website: xekhach.com
                        </small>
                    </div>
                </div>
            </div>

            <!-- Thông tin bổ sung -->
            <div class="col-lg-7">
                <!-- Thời gian -->
                <?php if ($ve_xe['TinhTrang'] == 'Chờ khởi hành'): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <h6 class="text-warning">
                                <i class="fas fa-clock me-2"></i>Thời gian còn lại đến giờ khởi hành
                            </h6>
                            <div id="countdown" class="h3 text-primary">
                                <!-- Sẽ được cập nhật bằng JavaScript -->
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Thông tin tài xế -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie text-primary me-2"></i>
                            Thông tin tài xế
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tai_xe_list)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-times fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có tài xế được phân công</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($tai_xe_list as $tai_xe): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($tai_xe['HoTen']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($tai_xe['MaTaiXe']); ?></small><br>
                                                        <span class="badge <?php echo $tai_xe['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary'; ?> small">
                                                    <?php echo $tai_xe['TenVaiTro']; ?>
                                                </span>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <a href="tel:<?php echo $tai_xe['SDT']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-phone me-1"></i><?php echo $tai_xe['SDT']; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hành khách khác trong chuyến -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-info me-2"></i>
                            Hành khách khác (<?php echo count($ve_khac); ?> người)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ve_khac)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-friends fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Chỉ có bạn trên chuyến xe này</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($ve_khac as $khach): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center p-2 bg-light rounded">
                                            <span class="badge bg-info me-2"><?php echo htmlspecialchars($khach['ViTri']); ?></span>
                                            <div class="flex-grow-1">
                                                <small class="fw-bold"><?php echo htmlspecialchars($khach['TenHanhKhach']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Thống kê ghế -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="text-success">
                                            <i class="fas fa-users fa-lg"></i>
                                        </div>
                                        <small class="text-muted">Đã bán</small><br>
                                        <strong class="text-success"><?php echo count($ve_khac) + 1; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-info">
                                            <i class="fas fa-chair fa-lg"></i>
                                        </div>
                                        <small class="text-muted">Còn trống</small><br>
                                        <strong class="text-info"><?php echo ($ve_xe['SoGhe'] - 2) - (count($ve_khac) + 1); ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-secondary">
                                            <i class="fas fa-clipboard-list fa-lg"></i>
                                        </div>
                                        <small class="text-muted">Tổng ghế</small><br>
                                        <strong class="text-secondary"><?php echo $ve_xe['SoGhe'] - 2; ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($ve_xe['TinhTrang'] == 'Chờ khởi hành'): ?>
    <script>
        // Đếm ngược thời gian
        const departureTime = new Date('<?php echo $ve_xe['GioDi']; ?>').getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = departureTime - now;

            if (distance > 0) {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                let countdownText = '';
                if (days > 0) {
                    countdownText = days + "d " + hours + "h " + minutes + "m";
                } else {
                    countdownText = hours + "h " + minutes + "m " + seconds + "s";
                }

                document.getElementById('countdown').innerHTML = countdownText;

                // Thay đổi màu khi sắp đến giờ
                if (hours < 1 && days === 0) {
                    document.getElementById('countdown').className = 'h3 text-danger';
                } else if (hours < 24 && days === 0) {
                    document.getElementById('countdown').className = 'h3 text-warning';
                }
            } else {
                document.getElementById('countdown').innerHTML = 'Đã khởi hành';
                document.getElementById('countdown').className = 'h3 text-danger';
            }
        }

        // Cập nhật mỗi giây
        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
<?php endif; ?>

<!-- CSS for print -->
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #ticket-card, #ticket-card * {
            visibility: visible;
        }
        #ticket-card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        /* Ẩn các nút khi in */
        .btn, .breadcrumb {
            display: none !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
