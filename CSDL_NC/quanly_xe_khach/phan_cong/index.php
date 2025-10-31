<?php
$page_title = "Quản lý phân công";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Xử lý xóa
if (isset($_GET['delete_chuyen']) && isset($_GET['delete_tai_xe'])) {
    $ma_chuyen_xe = $_GET['delete_chuyen'];
    $ma_tai_xe = $_GET['delete_tai_xe'];

    try {
        $query = "DELETE FROM phan_cong WHERE MaChuyenXe = ? AND MaTaiXe = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$ma_chuyen_xe, $ma_tai_xe]);
        $success = "Xóa phân công thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách phân công
try {
    $query = "SELECT pc.*, 
                     cx.GioDi, cx.GioDen, cx.TrangThai as TrangThaiChuyen,
                     td.DiemDau, td.DiemCuoi, td.DoDai,
                     xk.MaXe, lx.TenLoaiXe,
                     tx.HoTen, tx.SDT,
                     CASE pc.VaiTro
                         WHEN 1 THEN 'Tài xế chính'
                         WHEN 2 THEN 'Lái phụ'
                         ELSE 'Không xác định'
                     END as TenVaiTro,
                     CASE cx.TrangThai 
                         WHEN 1 THEN 'Chờ khởi hành'
                         WHEN 2 THEN 'Hoàn thành'
                         ELSE 'Không xác định'
                     END as TenTrangThaiChuyen,
                     CASE 
                         WHEN cx.GioDi <= NOW() AND cx.TrangThai = 1 THEN 'Đang diễn ra'
                         WHEN cx.GioDi > NOW() AND cx.TrangThai = 1 THEN 'Sắp khởi hành'
                         WHEN cx.TrangThai = 2 THEN 'Đã hoàn thành'
                         ELSE 'Bình thường'
                     END as TinhTrang
              FROM phan_cong pc
              LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
              ORDER BY cx.GioDi DESC, pc.VaiTro";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $phan_cong_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2><i class="fas fa-users text-primary me-2"></i>Quản lý phân công</h2>
                <p class="text-muted mb-0">Phân công tài xế cho các chuyến xe</p>
            </div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tạo phân công
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
                        <button class="btn btn-outline-info btn-sm filter-btn" data-filter="main_driver">
                            <i class="fas fa-user-tie me-1"></i>Tài xế chính
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary btn-sm filter-btn" data-filter="assistant">
                            <i class="fas fa-user-friends me-1"></i>Lái phụ
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-success btn-sm filter-btn" data-filter="upcoming">
                            <i class="fas fa-clock me-1"></i>Sắp khởi hành
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
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách phân công</h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Tổng: <?php echo count($phan_cong_list); ?> phân công</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($phan_cong_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có phân công nào</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tạo phân công đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="phanCongTable">
                            <thead class="table-dark">
                            <tr>
                                <th width="12%">Chuyến xe</th>
                                <th width="20%">Tuyến đường</th>
                                <th width="15%">Tài xế</th>
                                <th width="12%">Vai trò</th>
                                <th width="18%">Thời gian</th>
                                <th width="10%">Thu lao</th>
                                <th width="8%">Trạng thái</th>
                                <th width="5%">Thao tác</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($phan_cong_list as $pc): ?>
                                <tr data-role="<?php echo strtolower(str_replace(' ', '_', $pc['TenVaiTro'])); ?>"
                                    data-status="<?php echo strtolower(str_replace(' ', '_', $pc['TinhTrang'])); ?>">
                                    <td>
                                        <div class="small">
                                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($pc['MaChuyenXe']); ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($pc['MaXe']); ?></div>
                                            <small class="text-info"><?php echo htmlspecialchars($pc['TenLoaiXe']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($pc['DiemDau']); ?></div>
                                            <div class="text-primary">
                                                <i class="fas fa-arrow-down me-1"></i>
                                                <?php echo number_format($pc['DoDai'], 0); ?>km
                                            </div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($pc['DiemCuoi']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-bold"><?php echo htmlspecialchars($pc['HoTen']); ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($pc['MaTaiXe']); ?></div>
                                            <small class="text-info">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($pc['SDT']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $vai_tro_class = $pc['VaiTro'] == 1 ? 'bg-primary' : 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $vai_tro_class; ?> small">
                                        <?php echo $pc['TenVaiTro']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Đi:</strong> <?php echo date('d/m H:i', strtotime($pc['GioDi'])); ?></div>
                                            <div><strong>Đến:</strong> <?php echo date('d/m H:i', strtotime($pc['GioDen'])); ?></div>
                                            <small class="text-muted">
                                                <?php
                                                $duration = (strtotime($pc['GioDen']) - strtotime($pc['GioDi'])) / 3600;
                                                echo number_format($duration, 1) . 'h';
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                    <span class="fw-bold text-success">
                                        <?php echo number_format($pc['ThuLao'], 0, ',', '.'); ?>đ
                                    </span>
                                    </td>
                                    <td>
                                        <?php
                                        $trang_thai_class = '';
                                        switch($pc['TinhTrang']) {
                                            case 'Sắp khởi hành': $trang_thai_class = 'bg-warning text-dark'; break;
                                            case 'Đang diễn ra': $trang_thai_class = 'bg-info'; break;
                                            case 'Đã hoàn thành': $trang_thai_class = 'bg-success'; break;
                                            default: $trang_thai_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $trang_thai_class; ?> small">
                                        <?php echo $pc['TinhTrang']; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical" role="group">
                                            <a href="view.php?chuyen=<?php echo $pc['MaChuyenXe']; ?>&tai_xe=<?php echo $pc['MaTaiXe']; ?>"
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($pc['TrangThaiChuyen'] == 1): ?>
                                                <a href="edit.php?chuyen=<?php echo $pc['MaChuyenXe']; ?>&tai_xe=<?php echo $pc['MaTaiXe']; ?>"
                                                   class="btn btn-sm btn-outline-warning"
                                                   data-bs-toggle="tooltip" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?delete_chuyen=<?php echo $pc['MaChuyenXe']; ?>&delete_tai_xe=<?php echo $pc['MaTaiXe']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Xóa"
                                                   data-confirm="Bạn có chắc muốn xóa phân công này?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Chuyến đã hoàn thành">
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
            const rows = document.querySelectorAll('#phanCongTable tbody tr');

            // Update active button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter rows
            rows.forEach(row => {
                const role = row.getAttribute('data-role');
                const status = row.getAttribute('data-status');

                switch(filter) {
                    case 'all':
                        row.style.display = '';
                        break;
                    case 'main_driver':
                        row.style.display = role === 'tài_xế_chính' ? '' : 'none';
                        break;
                    case 'assistant':
                        row.style.display = role === 'lái_phụ' ? '' : 'none';
                        break;
                    case 'upcoming':
                        row.style.display = status === 'sắp_khởi_hành' ? '' : 'none';
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
