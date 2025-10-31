<?php
$page_title = "Quản lý vé xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Chỉ cho phép xóa vé của chuyến xe chưa khởi hành
        $check_query = "SELECT cx.TrangThai, cx.GioDi 
                       FROM ve_xe vx 
                       LEFT JOIN chuyen_xe cx ON vx.MaChuyenXe = cx.MaChuyenXe 
                       WHERE vx.MaVe = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $trip_info = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trip_info) {
            $error = "Không tìm thấy vé!";
        } elseif ($trip_info['TrangThai'] == 2) {
            $error = "Không thể xóa vé của chuyến xe đã hoàn thành!";
        } elseif (strtotime($trip_info['GioDi']) <= time()) {
            $error = "Không thể xóa vé của chuyến xe đã khởi hành!";
        } else {
            $query = "DELETE FROM ve_xe WHERE MaVe = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Xóa vé thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách vé xe
try {
    $query = "SELECT vx.*, 
                     cx.GioDi, cx.GioDen, cx.TrangThai as TrangThaiChuyen,
                     td.DiemDau, td.DiemCuoi, td.DoDai,
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
              ORDER BY cx.GioDi DESC, vx.ViTri";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $ve_xe_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-ticket-alt text-primary me-2"></i>Quản lý vé xe</h2>
                <p class="text-muted mb-0">Quản lý bán vé và thông tin hành khách</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Bán vé mới
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

        <!-- Bộ lọc nhanh -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-filter="all">
                            <i class="fas fa-list me-1"></i>Tất cả
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-warning btn-sm filter-btn" data-filter="waiting">
                            <i class="fas fa-clock me-1"></i>Chờ khởi hành
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-info btn-sm filter-btn" data-filter="traveling">
                            <i class="fas fa-route me-1"></i>Đang di chuyển
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-success btn-sm filter-btn" data-filter="completed">
                            <i class="fas fa-check-circle me-1"></i>Đã hoàn thành
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê nhanh -->
        <div class="row mb-4">
            <?php
            // Tính thống kê
            $total_tickets = count($ve_xe_list);
            $waiting_tickets = count(array_filter($ve_xe_list, function($v) { return $v['TinhTrang'] == 'Chờ khởi hành'; }));
            $traveling_tickets = count(array_filter($ve_xe_list, function($v) { return $v['TinhTrang'] == 'Đang di chuyển'; }));
            $completed_tickets = count(array_filter($ve_xe_list, function($v) { return $v['TinhTrang'] == 'Đã hoàn thành'; }));
            $total_revenue = array_sum(array_column($ve_xe_list, 'GiaVeThucTe'));
            ?>
            <div class="col-md-2-4 col-6 mb-3">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <div class="text-primary mb-2">
                            <i class="fas fa-ticket-alt fa-2x"></i>
                        </div>
                        <h4 class="text-primary"><?php echo $total_tickets; ?></h4>
                        <small class="text-muted">Tổng vé</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 mb-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <div class="text-warning mb-2">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <h4 class="text-warning"><?php echo $waiting_tickets; ?></h4>
                        <small class="text-muted">Chờ khởi hành</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 mb-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <div class="text-info mb-2">
                            <i class="fas fa-route fa-2x"></i>
                        </div>
                        <h4 class="text-info"><?php echo $traveling_tickets; ?></h4>
                        <small class="text-muted">Đang di chuyển</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 mb-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <div class="text-success mb-2">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h4 class="text-success"><?php echo $completed_tickets; ?></h4>
                        <small class="text-muted">Hoàn thành</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4 col-6 mb-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <div class="text-danger mb-2">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                        <h4 class="text-danger"><?php echo number_format($total_revenue/1000000, 1); ?>M</h4>
                        <small class="text-muted">Doanh thu</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách vé xe</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo $total_tickets; ?> vé</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ve_xe_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có vé nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Bán vé đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="veXeTable">
                            <thead class="table-dark">
                            <tr>
                                <th width="10%">Mã vé</th>
                                <th width="12%">Chuyến xe</th>
                                <th width="18%">Tuyến đường</th>
                                <th width="15%">Hành khách</th>
                                <th width="8%">Vị trí</th>
                                <th width="15%">Thời gian</th>
                                <th width="10%">Giá vé</th>
                                <th width="8%">Trạng thái</th>
                                <th width="4%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ve_xe_list as $ve): ?>
                                <tr data-status="<?php echo strtolower(str_replace(' ', '_', $ve['TinhTrang'])); ?>">
                                    <td>
                                        <span class="fw-bold text-primary small"><?php echo htmlspecialchars($ve['MaVe']); ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-bold text-info"><?php echo htmlspecialchars($ve['MaChuyenXe']); ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($ve['MaXe']); ?></div>
                                            <small class="text-secondary"><?php echo htmlspecialchars($ve['TenLoaiXe']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($ve['DiemDau']); ?></div>
                                            <div class="text-primary">
                                                <i class="fas fa-arrow-down me-1"></i>
                                                <?php echo number_format($ve['DoDai'], 0); ?>km
                                            </div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($ve['DiemCuoi']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-bold"><?php echo htmlspecialchars($ve['TenHanhKhach']); ?></div>
                                            <div class="text-info">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($ve['SDTHanhKhach']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($ve['ViTri']); ?>
                                    </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Đi:</strong> <?php echo date('d/m H:i', strtotime($ve['GioDi'])); ?></div>
                                            <div><strong>Đến:</strong> <?php echo date('d/m H:i', strtotime($ve['GioDen'])); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?php if ($ve['GiaVeThucTe']): ?>
                                                <span class="fw-bold text-success">
                                                <?php echo number_format($ve['GiaVeThucTe'], 0, ',', '.'); ?>đ
                                            </span>
                                            <?php else: ?>
                                                <span class="text-muted">Chưa cập nhật</span>
                                            <?php endif; ?>
                                            <?php if ($ve['GiaVeNiemYet']): ?>
                                                <br><small class="text-muted">
                                                    NY: <?php echo number_format($ve['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $trang_thai_class = '';
                                        switch($ve['TinhTrang']) {
                                            case 'Chờ khởi hành': $trang_thai_class = 'bg-warning text-dark'; break;
                                            case 'Đang di chuyển': $trang_thai_class = 'bg-info'; break;
                                            case 'Đã hoàn thành': $trang_thai_class = 'bg-success'; break;
                                            default: $trang_thai_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $trang_thai_class; ?> small">
                                        <?php echo $ve['TinhTrang']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical" role="group">
                                            <a href="view.php?id=<?php echo $ve['MaVe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($ve['TrangThaiChuyen'] == 1 && strtotime($ve['GioDi']) > time()): ?>
                                                <a href="edit.php?id=<?php echo $ve['MaVe']; ?>"
                                                   class="btn btn-sm btn-outline-warning"
                                                   data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?delete=<?php echo $ve['MaVe']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Hủy vé"
                                                   data-confirm="Bạn có chắc muốn hủy vé này?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Không thể sửa/hủy">
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

<script>
    // Bộ lọc
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            const rows = document.querySelectorAll('#veXeTable tbody tr');

            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter rows
            rows.forEach(row => {
                const status = row.getAttribute('data-status');

                switch(filter) {
                    case 'all':
                        row.style.display = '';
                        break;
                    case 'waiting':
                        row.style.display = status === 'chờ_khởi_hành' ? '' : 'none';
                        break;
                    case 'traveling':
                        row.style.display = status === 'đang_di_chuyển' ? '' : 'none';
                        break;
                    case 'completed':
                        row.style.display = status === 'đã_hoàn_thành' ? '' : 'none';
                        break;
                }
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

    .btn-group-vertical .btn {
        margin-bottom: 2px;
    }

    .btn-group-vertical .btn:last-child {
        margin-bottom: 0;
    }

    .col-md-2-4 {
        flex: 0 0 20%;
        max-width: 20%;
    }

    @media (max-width: 768px) {
        .col-md-2-4 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
