<?php
$page_title = "Quản lý chuyến xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Kiểm tra chuyến xe có vé đã bán không
        $check_query = "SELECT COUNT(*) FROM ve_xe WHERE MaChuyenXe = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$id]);
        $count_ve = $check_stmt->fetchColumn();

        // Kiểm tra phân công
        $check_query2 = "SELECT COUNT(*) FROM phan_cong WHERE MaChuyenXe = ?";
        $check_stmt2 = $db->prepare($check_query2);
        $check_stmt2->execute([$id]);
        $count_phan_cong = $check_stmt2->fetchColumn();

        if ($count_ve > 0) {
            $error = "Không thể xóa chuyến xe này vì đã có vé được bán!";
        } elseif ($count_phan_cong > 0) {
            $error = "Không thể xóa chuyến xe này vì đã có phân công tài xế!";
        } else {
            $query = "DELETE FROM chuyen_xe WHERE MaChuyenXe = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $success = "Xóa chuyến xe thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách chuyến xe
try {
    $query = "SELECT cx.*, 
                     td.DiemDau, td.DiemCuoi, td.DoDai,
                     xk.MaXe, lx.TenLoaiXe, lx.SoGhe,
                     COUNT(vx.MaVe) as SoVeDaBan,
                     (lx.SoGhe - 2) as SoGheToiDa,
                     COUNT(pc.MaTaiXe) as SoTaiXe,
                     CASE cx.TrangThai 
                         WHEN 1 THEN 'Chờ khởi hành'
                         WHEN 2 THEN 'Hoàn thành'
                         ELSE 'Không xác định'
                     END as TenTrangThai,
                     CASE 
                         WHEN cx.GioDi <= NOW() AND cx.TrangThai = 1 THEN 'Trễ giờ'
                         WHEN cx.GioDi > NOW() AND cx.TrangThai = 1 THEN 'Sắp khởi hành'
                         WHEN cx.TrangThai = 2 THEN 'Đã hoàn thành'
                         ELSE 'Bình thường'
                     END as TinhTrang
              FROM chuyen_xe cx
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
              LEFT JOIN phan_cong pc ON cx.MaChuyenXe = pc.MaChuyenXe
              GROUP BY cx.MaChuyenXe
              ORDER BY cx.GioDi DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $chuyen_xe_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-route text-primary me-2"></i>Quản lý chuyến xe</h2>
                <p class="text-muted mb-0">Quản lý lịch trình và chuyến đi của xe khách</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tạo chuyến xe
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
                        <button class="btn btn-outline-success btn-sm filter-btn" data-filter="completed">
                            <i class="fas fa-check-circle me-1"></i>Hoàn thành
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-danger btn-sm filter-btn" data-filter="delayed">
                            <i class="fas fa-exclamation-triangle me-1"></i>Trễ giờ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách chuyến xe</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($chuyen_xe_list); ?> chuyến</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($chuyen_xe_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-route fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có chuyến xe nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tạo chuyến xe đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="chuyenXeTable">
                            <thead class="table-dark">
                            <tr>
                                <th width="12%">Mã chuyến</th>
                                <th width="20%">Tuyến đường</th>
                                <th width="15%">Xe</th>
                                <th width="18%">Thời gian</th>
                                <th width="10%">Vé bán</th>
                                <th width="10%">Tài xế</th>
                                <th width="10%">Tình trạng</th>
                                <th width="5%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($chuyen_xe_list as $chuyen): ?>
                                <tr data-status="<?php echo strtolower(str_replace(' ', '_', $chuyen['TenTrangThai'])); ?>"
                                    data-condition="<?php echo strtolower(str_replace(' ', '_', $chuyen['TinhTrang'])); ?>">
                                    <td>
                                        <span class="fw-bold text-primary small"><?php echo htmlspecialchars($chuyen['MaChuyenXe']); ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($chuyen['DiemDau']); ?></div>
                                            <div class="text-primary">
                                                <i class="fas fa-arrow-down me-1"></i>
                                                <?php echo number_format($chuyen['DoDai'], 0); ?>km
                                            </div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($chuyen['DiemCuoi']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-bold"><?php echo htmlspecialchars($chuyen['MaXe']); ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($chuyen['TenLoaiXe']); ?></div>
                                            <small class="text-info"><?php echo $chuyen['SoGhe']; ?> ghế</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Đi:</strong> <?php echo date('d/m H:i', strtotime($chuyen['GioDi'])); ?></div>
                                            <div><strong>Đến:</strong> <?php echo date('d/m H:i', strtotime($chuyen['GioDen'])); ?></div>
                                            <small class="text-muted">
                                                <?php
                                                $duration = (strtotime($chuyen['GioDen']) - strtotime($chuyen['GioDi'])) / 3600;
                                                echo number_format($duration, 1) . 'h';
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $ti_le = $chuyen['SoGheToiDa'] > 0 ? ($chuyen['SoVeDaBan'] / $chuyen['SoGheToiDa']) * 100 : 0;
                                        $badge_class = $ti_le >= 80 ? 'bg-success' : ($ti_le >= 50 ? 'bg-warning text-dark' : 'bg-info');
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> small">
                                        <?php echo $chuyen['SoVeDaBan']; ?>/<?php echo $chuyen['SoGheToiDa']; ?>
                                    </span>
                                        <div class="progress mt-1" style="height: 3px;">
                                            <div class="progress-bar" style="width: <?php echo $ti_le; ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($chuyen['SoTaiXe'] > 0): ?>
                                            <span class="badge bg-success small">
                                            <i class="fas fa-user-check me-1"></i><?php echo $chuyen['SoTaiXe']; ?>
                                        </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark small">
                                            <i class="fas fa-user-times me-1"></i>Chưa có
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $trang_thai_class = '';
                                        $tinh_trang_class = '';

                                        switch($chuyen['TenTrangThai']) {
                                            case 'Chờ khởi hành': $trang_thai_class = 'bg-warning text-dark'; break;
                                            case 'Hoàn thành': $trang_thai_class = 'bg-success'; break;
                                            default: $trang_thai_class = 'bg-secondary'; break;
                                        }

                                        if ($chuyen['TinhTrang'] == 'Trễ giờ') {
                                            $tinh_trang_class = 'bg-danger';
                                        }
                                        ?>
                                        <div>
                                        <span class="badge <?php echo $trang_thai_class; ?> small d-block mb-1">
                                            <?php echo $chuyen['TenTrangThai']; ?>
                                        </span>
                                            <?php if ($chuyen['TinhTrang'] == 'Trễ giờ'): ?>
                                                <span class="badge <?php echo $tinh_trang_class; ?> small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Trễ
                                        </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical" role="group">
                                            <a href="view.php?id=<?php echo $chuyen['MaChuyenXe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($chuyen['TrangThai'] == 1): ?>
                                                <a href="edit.php?id=<?php echo $chuyen['MaChuyenXe']; ?>"
                                                   class="btn btn-sm btn-outline-warning"
                                                   data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($chuyen['SoVeDaBan'] == 0 && $chuyen['SoTaiXe'] == 0): ?>
                                                <a href="index.php?delete=<?php echo $chuyen['MaChuyenXe']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa chuyến xe này?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Không thể xóa">
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
            const rows = document.querySelectorAll('#chuyenXeTable tbody tr');

            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter rows
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const condition = row.getAttribute('data-condition');

                switch(filter) {
                    case 'all':
                        row.style.display = '';
                        break;
                    case 'waiting':
                        row.style.display = status === 'chờ_khởi_hành' ? '' : 'none';
                        break;
                    case 'completed':
                        row.style.display = status === 'hoàn_thành' ? '' : 'none';
                        break;
                    case 'delayed':
                        row.style.display = condition === 'trễ_giờ' ? '' : 'none';
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
</style>

<?php include '../includes/footer.php'; ?>
