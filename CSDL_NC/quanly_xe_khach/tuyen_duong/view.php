<?php
$page_title = "Chi tiết tuyến đường";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin tuyến đường chi tiết
try {
    $query = "SELECT * FROM tuyen_duong WHERE MaTuyenDuong = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $tuyen_duong = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tuyen_duong) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách chuyến xe trên tuyến này
    $query_chuyen = "SELECT cx.*, xk.MaXe, lx.TenLoaiXe,
                            COUNT(vx.MaVe) as SoVeDaBan,
                            (lx.SoGhe - 2) as SoGheToiDa,
                            CASE cx.TrangThai 
                                WHEN 1 THEN 'Chờ khởi hành'
                                WHEN 2 THEN 'Hoàn thành'
                                ELSE 'Không xác định'
                            END as TenTrangThai
                     FROM chuyen_xe cx
                     LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
                     LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
                     LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
                     WHERE cx.MaTuyenDuong = ?
                     GROUP BY cx.MaChuyenXe
                     ORDER BY cx.GioDi DESC
                     LIMIT 10";
    $stmt_chuyen = $db->prepare($query_chuyen);
    $stmt_chuyen->execute([$id]);
    $danh_sach_chuyen = $stmt_chuyen->fetchAll(PDO::FETCH_ASSOC);

    // Lấy bảng giá cho tuyến này
    $query_gia = "SELECT bg.*, lx.TenLoaiXe, lx.SoGhe
                  FROM bang_gia bg
                  LEFT JOIN loai_xe lx ON bg.MaLoaiXe = lx.MaLoaiXe
                  WHERE bg.MaTuyenDuong = ?
                  ORDER BY bg.NgayBatDau DESC, bg.MaLoaiXe";
    $stmt_gia = $db->prepare($query_gia);
    $stmt_gia->execute([$id]);
    $bang_gia = $stmt_gia->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê
    $query_stats = "SELECT 
                        COUNT(DISTINCT cx.MaChuyenXe) as TongChuyenXe,
                        COUNT(DISTINCT CASE WHEN cx.TrangThai = 1 THEN cx.MaChuyenXe END) as ChuyenDangChay,
                        COUNT(DISTINCT CASE WHEN cx.TrangThai = 2 THEN cx.MaChuyenXe END) as ChuyenHoanThanh,
                        COUNT(DISTINCT bg.MaLoaiXe) as SoBangGia
                    FROM chuyen_xe cx
                    LEFT JOIN bang_gia bg ON cx.MaTuyenDuong = bg.MaTuyenDuong
                    WHERE cx.MaTuyenDuong = ?";
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->execute([$id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

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
                <h2><i class="fas fa-eye text-info me-2"></i>Chi tiết tuyến đường</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Tuyến đường</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($tuyen_duong['MaTuyenDuong']); ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="edit.php?id=<?php echo $tuyen_duong['MaTuyenDuong']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa
                </a>
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
            <!-- Thông tin tuyến đường -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-route text-primary me-2"></i>Thông tin tuyến
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-route fa-3x"></i>
                            </div>
                        </div>

                        <h4 class="text-primary mb-3"><?php echo htmlspecialchars($tuyen_duong['MaTuyenDuong']); ?></h4>

                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <span class="badge bg-success fs-6 me-2"><?php echo htmlspecialchars($tuyen_duong['DiemDau']); ?></span>
                                <i class="fas fa-arrow-right text-primary fa-lg mx-2"></i>
                                <span class="badge bg-danger fs-6 ms-2"><?php echo htmlspecialchars($tuyen_duong['DiemCuoi']); ?></span>
                            </div>
                        </div>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold text-muted">Độ dài:</td>
                                <td><span class="badge bg-info"><?php echo number_format($tuyen_duong['DoDai'], 1); ?> km</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Độ phức tạp:</td>
                                <td>
                                    <?php
                                    $phuc_tap = $tuyen_duong['DoPhucTap'];
                                    $badge_class = '';
                                    $text = '';
                                    switch($phuc_tap) {
                                        case 1:
                                            $badge_class = 'bg-success';
                                            $text = 'Đơn giản';
                                            break;
                                        case 2:
                                            $badge_class = 'bg-warning text-dark';
                                            $text = 'Trung bình';
                                            break;
                                        case 3:
                                            $badge_class = 'bg-danger';
                                            $text = 'Phức tạp';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $text; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Thời gian ước tính:</td>
                                <td>
                                    <?php
                                    // Tính thời gian ước tính dựa trên độ dài và độ phức tạp
                                    $toc_do_tb = 50; // km/h cơ bản
                                    switch($phuc_tap) {
                                        case 1: $toc_do_tb = 60; break; // Đường tốt
                                        case 2: $toc_do_tb = 50; break; // Đường bình thường
                                        case 3: $toc_do_tb = 40; break; // Đường khó
                                    }
                                    $thoi_gian = $tuyen_duong['DoDai'] / $toc_do_tb;
                                    $gio = floor($thoi_gian);
                                    $phut = round(($thoi_gian - $gio) * 60);
                                    ?>
                                    <span class="badge bg-secondary">
                                        <?php echo ($gio > 0 ? $gio . 'h ' : '') . $phut . 'p'; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Thống kê và hoạt động -->
            <div class="col-lg-8">
                <!-- Thống kê -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-bus fa-2x"></i>
                                </div>
                                <h4 class="text-primary"><?php echo $stats['TongChuyenXe']; ?></h4>
                                <small class="text-muted">Tổng chuyến</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h4 class="text-warning"><?php echo $stats['ChuyenDangChay']; ?></h4>
                                <small class="text-muted">Đang chạy</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <h4 class="text-success"><?php echo $stats['ChuyenHoanThanh']; ?></h4>
                                <small class="text-muted">Hoàn thành</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <div class="text-info mb-2">
                                    <i class="fas fa-tags fa-2x"></i>
                                </div>
                                <h4 class="text-info"><?php echo $stats['SoBangGia']; ?></h4>
                                <small class="text-muted">Bảng giá</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảng giá -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-tags text-success me-2"></i>
                                    Bảng giá theo loại xe
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../bang_gia/create.php?tuyen=<?php echo $tuyen_duong['MaTuyenDuong']; ?>"
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i>Thêm giá
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bang_gia)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-tags fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Chưa có bảng giá cho tuyến này</p>
                                <a href="../bang_gia/create.php?tuyen=<?php echo $tuyen_duong['MaTuyenDuong']; ?>"
                                   class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Thêm bảng giá
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Loại xe</th>
                                        <th>Số ghế</th>
                                        <th>Giá vé</th>
                                        <th>Thời gian áp dụng</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($bang_gia as $gia): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?php echo htmlspecialchars($gia['TenLoaiXe']); ?></span>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($gia['MaLoaiXe']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $gia['SoGhe']; ?> ghế</span>
                                            </td>
                                            <td>
                                            <span class="fw-bold text-success">
                                                <?php echo number_format($gia['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                            </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Từ:</strong> <?php echo date('d/m/Y', strtotime($gia['NgayBatDau'])); ?><br>
                                                    <strong>Đến:</strong>
                                                    <?php echo $gia['NgayKetThuc'] ? date('d/m/Y', strtotime($gia['NgayKetThuc'])) : 'Không giới hạn'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $now = date('Y-m-d');
                                                $bat_dau = $gia['NgayBatDau'];
                                                $ket_thuc = $gia['NgayKetThuc'];

                                                if ($bat_dau > $now) {
                                                    echo '<span class="badge bg-warning text-dark">Chưa áp dụng</span>';
                                                } elseif ($ket_thuc && $ket_thuc < $now) {
                                                    echo '<span class="badge bg-secondary">Đã hết hạn</span>';
                                                } else {
                                                    echo '<span class="badge bg-success">Đang áp dụng</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lịch sử chuyến xe -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-history text-info me-2"></i>
                                    Lịch sử chuyến xe (10 chuyến gần nhất)
                                </h5>
                            </div>
                            <div class="col-auto">
                                <a href="../chuyen_xe/create.php?tuyen=<?php echo $tuyen_duong['MaTuyenDuong']; ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i>Tạo chuyến
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($danh_sach_chuyen)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-3">Chưa có chuyến xe nào trên tuyến này</p>
                            <a href="../chuyen_xe/create.php?tuyen=<?php echo $tuyen_duong['MaTuyenDuong']; ?>"
                               class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tạo chuyến đầu tiên                            </a>
                        </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Mã chuyến</th>
                                        <th>Xe</th>
                                        <th>Thời gian</th>
                                        <th>Vé bán</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($danh_sach_chuyen as $chuyen): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($chuyen['MaChuyenXe']); ?></small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <strong><?php echo htmlspecialchars($chuyen['MaXe']); ?></strong><br>
                                                    <span class="text-muted"><?php echo htmlspecialchars($chuyen['TenLoaiXe']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <strong>Đi:</strong> <?php echo date('d/m/Y H:i', strtotime($chuyen['GioDi'])); ?><br>
                                                    <strong>Đến:</strong> <?php echo date('d/m/Y H:i', strtotime($chuyen['GioDen'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $ti_le = $chuyen['SoGheToiDa'] > 0 ? ($chuyen['SoVeDaBan'] / $chuyen['SoGheToiDa']) * 100 : 0;
                                                $badge_class = $ti_le >= 80 ? 'bg-success' : ($ti_le >= 50 ? 'bg-warning' : 'bg-info');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $chuyen['SoVeDaBan']; ?>/<?php echo $chuyen['SoGheToiDa']; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = $chuyen['TrangThai'] == 1 ? 'bg-warning text-dark' : 'bg-success';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $chuyen['TenTrangThai']; ?>
                                            </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../chuyen_xe/view.php?id=<?php echo $chuyen['MaChuyenXe']; ?>"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($chuyen['TrangThai'] == 1): ?>
                                                        <a href="../chuyen_xe/edit.php?id=<?php echo $chuyen['MaChuyenXe']; ?>"
                                                           class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

