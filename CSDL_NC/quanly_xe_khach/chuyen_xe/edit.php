<?php
$page_title = "Chỉnh sửa chuyến xe";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin chuyến xe hiện tại
try {
    $query = "SELECT cx.*, td.DiemDau, td.DiemCuoi, td.DoDai, td.DoPhucTap,
                     xk.MaXe, lx.TenLoaiXe, lx.SoGhe
              FROM chuyen_xe cx
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              WHERE cx.MaChuyenXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $chuyen_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chuyen_xe) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách xe khách available
    $query_xe = "SELECT xk.*, lx.TenLoaiXe, lx.SoGhe
                 FROM xe_khach xk 
                 LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
                 WHERE (xk.SoNgayBDConLai > 0 AND xk.HanDangKiem > CURDATE())
                 OR xk.MaXe = ?
                 ORDER BY xk.MaXe";
    $stmt_xe = $db->prepare($query_xe);
    $stmt_xe->execute([$chuyen_xe['MaXe']]);
    $xe_khach_list = $stmt_xe->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $ma_xe = trim($_POST['ma_xe']);
    $gio_di = $_POST['gio_di'];
    $gio_den = $_POST['gio_den'];
    $tai_xe_chinh = trim($_POST['tai_xe_chinh']);
    $lai_phu = trim($_POST['lai_phu']);

    // Validation
    if (empty($ma_xe) || empty($gio_di) || empty($gio_den) || empty($tai_xe_chinh) || empty($lai_phu)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif ($tai_xe_chinh == $lai_phu) {
        $error = "Lái chính phải khác lái phụ!";
    } elseif (strtotime($gio_den) <= strtotime($gio_di)) {
        $error = "Giờ đến phải sau giờ khởi hành!";
    } else {
        // Kiểm tra nếu đã có vé bán thì không được thay đổi thời gian quá nhiều
        $check_ve_query = "SELECT COUNT(*) FROM ve_xe WHERE MaChuyenXe = ?";
        $check_ve_stmt = $db->prepare($check_ve_query);
        $check_ve_stmt->execute([$id]);
        $so_ve_ban = $check_ve_stmt->fetchColumn();

        $old_departure = strtotime($chuyen_xe['GioDi']);
        $new_departure = strtotime($gio_di);
        $time_diff = abs($new_departure - $old_departure) / 3600; // giờ

        if ($so_ve_ban > 0 && $time_diff > 2) {
            $error = "Không thể thay đổi thời gian quá 2 giờ khi đã có vé được bán!";
        } else {
            // Kiểm thời gian hợp lý
            $duration_hours = (strtotime($gio_den) - strtotime($gio_di)) / 3600;
            if ($duration_hours < 1) {
                $error = "Thời gian di chuyển phải ít nhất 1 giờ!";
            } elseif ($duration_hours > 24) {
                $error = "Thời gian di chuyển không được quá 24 giờ!";
            } else {
                try {
                    // Kiểm tra xung đột lịch trình xe (trừ chuyến hiện tại)
                    $conflict_query = "SELECT COUNT(*) FROM chuyen_xe 
                                      WHERE MaXe = ? AND TrangThai = 1 AND MaChuyenXe != ?
                                      AND (
                                          (GioDi <= ? AND GioDen >= ?) OR
                                          (GioDi <= ? AND GioDen >= ?) OR
                                          (GioDi >= ? AND GioDen <= ?)
                                      )";
                    $conflict_stmt = $db->prepare($conflict_query);
                    $conflict_stmt->execute([$ma_xe, $id, $gio_di, $gio_di, $gio_den, $gio_den, $gio_di, $gio_den]);

                    if ($conflict_stmt->fetchColumn() > 0) {
                        $error = "Xe này đã có lịch trình trùng với thời gian đã chọn!";
                    } else {
                        $query = "UPDATE chuyen_xe SET MaXe = ?, GioDi = ?, GioDen = ? WHERE MaChuyenXe = ?";
                        $stmt = $db->prepare($query);

                        if ($stmt->execute([$ma_xe, $gio_di, $gio_den, $id])) {
                            $success = "Cập nhật chuyến xe thành công!";
                            // Cập nhật lại thông tin hiển thị
                            $chuyen_xe['MaXe'] = $ma_xe;
                            $chuyen_xe['GioDi'] = $gio_di;
                            $chuyen_xe['GioDen'] = $gio_den;
                        } else {
                            $error = "Có lỗi xảy ra khi cập nhật chuyến xe!";
                        }
                        // Xóa phân công cũ
                        $delete_query = "DELETE FROM phan_cong WHERE MaChuyenXe = ?";
                        $delete_stmt = $db->prepare($delete_query);
                        $delete_stmt->execute([$id]);
                        // Thêm phân công mới
                        $insert_query = "INSERT INTO phan_cong (MaChuyenXe, MaTaiXe, VaiTro, ThuLao) VALUES (?, ?, ?, 0)";
                        $insert_stmt = $db->prepare($insert_query);
                        // Tài xế chính
                        $insert_stmt->execute([$id, $tai_xe_chinh, 1]);
                        // Lái phụ
                        $insert_stmt->execute([$id, $lai_phu, 2]);
                    }
                } catch (PDOException $e) {
                    $error = "Lỗi: " . $e->getMessage();
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-edit text-warning me-2"></i>
                    Chỉnh sửa chuyến xe
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Chuyến xe</a></li>
                        <li class="breadcrumb-item active">Chỉnh sửa</li>
                    </ol>
                </nav>

                <!-- Thông báo -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($chuyen_xe): ?>
                    <!-- Thông tin không thay đổi -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle text-info me-2"></i>Thông tin cố định
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Mã chuyến:</strong><br>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($chuyen_xe['MaChuyenXe']); ?></span><br><br>
                                        <strong>Tuyến đường:</strong><br>
                                        <span class="fw-bold"><?php echo htmlspecialchars($chuyen_xe['DiemDau']); ?></span>
                                        <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                        <span class="fw-bold"><?php echo htmlspecialchars($chuyen_xe['DiemCuoi']); ?></span><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($chuyen_xe['MaTuyenDuong']); ?> -
                                            <?php echo number_format($chuyen_xe['DoDai'], 1); ?> km
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <?php
                                        // Kiểm tra số vé đã bán
                                        $check_ve_query = "SELECT COUNT(*) FROM ve_xe WHERE MaChuyenXe = ?";
                                        $check_ve_stmt = $db->prepare($check_ve_query);
                                        $check_ve_stmt->execute([$id]);
                                        $so_ve_ban = $check_ve_stmt->fetchColumn();
                                        ?>
                                        <strong>Vé đã bán:</strong><br>
                                        <?php if ($so_ve_ban > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                            <i class="fas fa-ticket-alt me-1"></i><?php echo $so_ve_ban; ?> vé
                                        </span>
                                            <div class="small text-warning mt-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Hạn chế thay đổi thời gian (tối đa 2h)
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có vé</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form chỉnh sửa -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-bus me-1"></i>Xe khách <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="ma_xe" required>
                                    <option value="">-- Chọn xe khách --</option>
                                    <?php foreach ($xe_khach_list as $xe): ?>
                                        <option value="<?php echo $xe['MaXe']; ?>"
                                                <?php echo(isset($_POST['ma_xe']) ?
                                                        ($_POST['ma_xe'] == $xe['MaXe'] ? 'selected' : '') :
                                                        ($chuyen_xe['MaXe'] == $xe['MaXe'] ? 'selected' : '')); ?>>
                                            <?php echo htmlspecialchars($xe['MaXe'] . ' - ' . $xe['TenLoaiXe'] . ' (' . $xe['SoGhe'] . ' ghế)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Chỉ hiển thị xe còn hạn bảo dưỡng và đăng kiểm</div>
                                <div class="invalid-feedback">Vui lòng chọn xe khách</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-toggle-on me-1"></i>Trạng thái <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="trang_thai" required>
                                    <option value="1" <?php echo(isset($_POST['trang_thai']) ?
                                            ($_POST['trang_thai'] == 1 ? 'selected' : '') :
                                            ($chuyen_xe['TrangThai'] == 1 ? 'selected' : '')); ?>>
                                        Chờ khởi hành
                                    </option>
                                    <option value="2" <?php echo(isset($_POST['trang_thai']) ?
                                            ($_POST['trang_thai'] == 2 ? 'selected' : '') :
                                            ($chuyen_xe['TrangThai'] == 2 ? 'selected' : '')); ?>>
                                        Hoàn thành
                                    </option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn trạng thái</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-play me-1 text-success"></i>Giờ khởi hành <span
                                            class="text-danger">*</span>
                                </label>
                                <input type="datetime-local"
                                       class="form-control"
                                       name="gio_di"
                                       id="gio-di"
                                       value="<?php echo isset($_POST['gio_di']) ? $_POST['gio_di'] : date('Y-m-d\TH:i', strtotime($chuyen_xe['GioDi'])); ?>"
                                       required>
                                <div class="invalid-feedback">Vui lòng chọn giờ khởi hành</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-stop me-1 text-danger"></i>Giờ đến dự kiến <span
                                            class="text-danger">*</span>
                                </label>
                                <input type="datetime-local"
                                       class="form-control"
                                       name="gio_den"
                                       id="gio-den"
                                       value="<?php echo isset($_POST['gio_den']) ? $_POST['gio_den'] : date('Y-m-d\TH:i', strtotime($chuyen_xe['GioDen'])); ?>"
                                       required>
                                <div class="invalid-feedback">Vui lòng chọn giờ đến</div>
                            </div>
                        </div>

                        <!-- Thông tin so sánh -->
                        <div class="mb-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>So sánh thay đổi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Thời gian hiện tại:</strong><br>
                                            <small class="text-muted">
                                                <strong>Đi:</strong> <?php echo date('d/m/Y H:i', strtotime($chuyen_xe['GioDi'])); ?>
                                                <br>
                                                <strong>Đến:</strong> <?php echo date('d/m/Y H:i', strtotime($chuyen_xe['GioDen'])); ?>
                                                <br>
                                                <strong>Thời lượng:</strong>
                                                <?php
                                                $current_duration = (strtotime($chuyen_xe['GioDen']) - strtotime($chuyen_xe['GioDi'])) / 3600;
                                                echo number_format($current_duration, 1) . 'h';
                                                ?>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Thời gian mới:</strong><br>
                                            <span id="new-schedule" class="text-success small">
                                            Sẽ cập nhật khi thay đổi
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <a href="view.php?id=<?php echo $chuyen_xe['MaChuyenXe']; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Xem chi tiết
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Cập nhật
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Cập nhật thông tin so sánh khi thay đổi thời gian
    function updateScheduleComparison() {
        const gioDi = document.getElementById('gio-di').value;
        const gioDen = document.getElementById('gio-den').value;
        const newSchedule = document.getElementById('new-schedule');

        if (gioDi && gioDen) {
            const departureDate = new Date(gioDi);
            const arrivalDate = new Date(gioDen);
            const duration = (arrivalDate - departureDate) / (1000 * 60 * 60); // hours

            if (duration > 0) {
                const departureStr = departureDate.toLocaleString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const arrivalStr = arrivalDate.toLocaleString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                newSchedule.innerHTML = `
                <strong>Đi:</strong> ${departureStr}<br>
                <strong>Đến:</strong> ${arrivalStr}<br>
                <strong>Thời lượng:</strong> ${duration.toFixed(1)}h
            `;
            } else {
                newSchedule.innerHTML = '<span class="text-danger">Giờ đến phải sau giờ đi</span>';
            }
        }
    }

    document.getElementById('gio-di').addEventListener('change', function () {
        document.getElementById('gio-den').min = this.value;
        updateScheduleComparison();
    });

    document.getElementById('gio-den').addEventListener('change', updateScheduleComparison);

    // Initial update
    updateScheduleComparison();
</script>

<?php include '../includes/footer.php'; ?>
