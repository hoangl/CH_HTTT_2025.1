<?php
$page_title = "Chỉnh sửa xe khách";
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

// Lấy thông tin xe khách hiện tại
try {
    $query = "SELECT xk.*, lx.TenLoaiXe, lx.SoGhe 
              FROM xe_khach xk 
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe 
              WHERE xk.MaXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $xe_khach = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$xe_khach) {
        header("Location: index.php");
        exit();
    }

    // Lấy danh sách loại xe
    $query_loai = "SELECT * FROM loai_xe ORDER BY MaLoaiXe";
    $stmt_loai = $db->prepare($query_loai);
    $stmt_loai->execute();
    $loai_xe_list = $stmt_loai->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $ma_loai_xe = trim($_POST['ma_loai_xe']);
    $so_ngay_bd = intval($_POST['so_ngay_bd']);
    $han_dang_kiem = $_POST['han_dang_kiem'];

    // Validation
    if (empty($ma_loai_xe) || empty($han_dang_kiem)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif ($so_ngay_bd < 0) {
        $error = "Số ngày bảo dưỡng còn lại không được âm!";
    } else {
        try {
            $query = "UPDATE xe_khach SET MaLoaiXe = ?, SoNgayBDConLai = ?, HanDangKiem = ? WHERE MaXe = ?";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$ma_loai_xe, $so_ngay_bd, $han_dang_kiem, $id])) {
                $success = "Cập nhật xe khách thành công!";
                // Cập nhật lại thông tin
                $xe_khach['MaLoaiXe'] = $ma_loai_xe;
                $xe_khach['SoNgayBDConLai'] = $so_ngay_bd;
                $xe_khach['HanDangKiem'] = $han_dang_kiem;

                // Lấy lại thông tin loại xe mới
                $query_new_loai = "SELECT TenLoaiXe, SoGhe FROM loai_xe WHERE MaLoaiXe = ?";
                $stmt_new_loai = $db->prepare($query_new_loai);
                $stmt_new_loai->execute([$ma_loai_xe]);
                $new_loai = $stmt_new_loai->fetch(PDO::FETCH_ASSOC);
                if ($new_loai) {
                    $xe_khach['TenLoaiXe'] = $new_loai['TenLoaiXe'];
                    $xe_khach['SoGhe'] = $new_loai['SoGhe'];
                }
            } else {
                $error = "Có lỗi xảy ra khi cập nhật xe khách!";
            }
        } catch(PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-edit text-warning me-2"></i>
                    Chỉnh sửa xe khách
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Xe khách</a></li>
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

                <?php if ($xe_khach): ?>
                    <!-- Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-id-card me-1"></i>Biển số xe
                                </label>
                                <input type="text"
                                       class="form-control bg-light"
                                       value="<?php echo htmlspecialchars($xe_khach['MaXe']); ?>"
                                       readonly>
                                <div class="form-text text-info">Biển số xe không thể thay đổi</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-car me-1"></i>Loại xe <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="ma_loai_xe" required>
                                    <option value="">-- Chọn loại xe --</option>
                                    <?php foreach ($loai_xe_list as $loai): ?>
                                        <option value="<?php echo $loai['MaLoaiXe']; ?>"
                                            <?php echo (isset($_POST['ma_loai_xe']) ?
                                                ($_POST['ma_loai_xe'] == $loai['MaLoaiXe'] ? 'selected' : '') :
                                                ($xe_khach['MaLoaiXe'] == $loai['MaLoaiXe'] ? 'selected' : '')); ?>>
                                            <?php echo htmlspecialchars($loai['MaLoaiXe'] . ' - ' . $loai['TenLoaiXe'] . ' (' . $loai['SoGhe'] . ' ghế)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn loại xe</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tools me-1"></i>Số ngày bảo dưỡng còn lại
                                </label>
                                <input type="number"
                                       class="form-control"
                                       name="so_ngay_bd"
                                       value="<?php echo isset($_POST['so_ngay_bd']) ? $_POST['so_ngay_bd'] : $xe_khach['SoNgayBDConLai']; ?>"
                                       min="0"
                                       max="365">
                                <div class="form-text">
                                    <?php
                                    $current_days = $xe_khach['SoNgayBDConLai'];
                                    if ($current_days <= 0) {
                                        echo '<span class="text-danger">⚠️ Xe cần bảo dưỡng ngay!</span>';
                                    } elseif ($current_days <= 10) {
                                        echo '<span class="text-warning">⚠️ Sắp đến hạn bảo dưỡng</span>';
                                    } else {
                                        echo '<span class="text-success">✓ Còn thời gian bảo dưỡng</span>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-certificate me-1"></i>Hạn đăng kiểm <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control"
                                       name="han_dang_kiem"
                                       value="<?php echo isset($_POST['han_dang_kiem']) ? $_POST['han_dang_kiem'] : $xe_khach['HanDangKiem']; ?>"
                                       required>
                                <div class="form-text">
                                    <?php
                                    $han_dk = strtotime($xe_khach['HanDangKiem']);
                                    $now = time();
                                    if ($han_dk <= $now) {
                                        echo '<span class="text-danger">⚠️ Đã hết hạn đăng kiểm!</span>';
                                    } elseif (($han_dk - $now) <= (30 * 24 * 3600)) {
                                        echo '<span class="text-warning">⚠️ Sắp hết hạn đăng kiểm</span>';
                                    } else {
                                        echo '<span class="text-success">✓ Còn hạn đăng kiểm</span>';
                                    }
                                    ?>
                                </div>
                                <div class="invalid-feedback">Vui lòng chọn hạn đăng kiểm</div>
                            </div>
                        </div>

                        <!-- Thông tin hiện tại -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle text-info me-2"></i>Thông tin hiện tại
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Biển số:</strong><br>
                                            <span class="text-primary"><?php echo htmlspecialchars($xe_khach['MaXe']); ?></span><br><br>
                                            <strong>Loại xe hiện tại:</strong><br>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($xe_khach['TenLoaiXe']); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Số ghế:</strong><br>
                                            <span class="badge bg-secondary"><?php echo $xe_khach['SoGhe']; ?> ghế</span><br><br>
                                            <strong>Trạng thái:</strong><br>
                                            <?php
                                            if ($xe_khach['SoNgayBDConLai'] <= 0 || strtotime($xe_khach['HanDangKiem']) <= time()) {
                                                echo '<span class="badge bg-danger">Cần bảo dưỡng/đăng kiểm</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Hoạt động tốt</span>';
                                            }
                                            ?>
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
                            <a href="view.php?id=<?php echo $xe_khach['MaXe']; ?>" class="btn btn-info">
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

<?php include '../includes/footer.php'; ?>
