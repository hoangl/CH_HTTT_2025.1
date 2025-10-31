<?php
$page_title = "Chỉnh sửa loại xe";
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

// Lấy thông tin loại xe hiện tại
try {
    $query = "SELECT * FROM loai_xe WHERE MaLoaiXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $loai_xe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loai_xe) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $ten_loai_xe = trim($_POST['ten_loai_xe']);
    $so_ghe = intval($_POST['so_ghe']);

    // Validation
    if (empty($ten_loai_xe) || $so_ghe <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin hợp lệ!";
    } elseif ($so_ghe > 60) {
        $error = "Số ghế không được vượt quá 60!";
    } else {
        try {
            $query = "UPDATE loai_xe SET TenLoaiXe = ?, SoGhe = ? WHERE MaLoaiXe = ?";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$ten_loai_xe, $so_ghe, $id])) {
                $success = "Cập nhật loại xe thành công!";
                // Cập nhật lại thông tin
                $loai_xe['TenLoaiXe'] = $ten_loai_xe;
                $loai_xe['SoGhe'] = $so_ghe;
            } else {
                $error = "Có lỗi xảy ra khi cập nhật loại xe!";
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
                    Chỉnh sửa loại xe
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Loại xe</a></li>
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

                <?php if ($loai_xe): ?>
                    <!-- Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tag me-1"></i>Mã loại xe
                                </label>
                                <input type="text"
                                       class="form-control bg-light"
                                       value="<?php echo htmlspecialchars($loai_xe['MaLoaiXe']); ?>"
                                       readonly>
                                <div class="form-text text-info">Mã loại xe không thể thay đổi</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-chair me-1"></i>Số ghế <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control"
                                       name="so_ghe"
                                       value="<?php echo isset($_POST['so_ghe']) ? $_POST['so_ghe'] : $loai_xe['SoGhe']; ?>"
                                       min="1"
                                       max="60"
                                       required>
                                <div class="form-text">Từ 1 đến 60 ghế</div>
                                <div class="invalid-feedback">Vui lòng nhập số ghế hợp lệ (1-60)</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-signature me-1"></i>Tên loại xe <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ten_loai_xe"
                                   value="<?php echo isset($_POST['ten_loai_xe']) ? htmlspecialchars($_POST['ten_loai_xe']) : htmlspecialchars($loai_xe['TenLoaiXe']); ?>"
                                   maxlength="100"
                                   required>
                            <div class="invalid-feedback">Vui lòng nhập tên loại xe</div>
                        </div>

                        <!-- Thông tin hiện tại -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle text-info me-2"></i>Thông tin hiện tại
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Mã:</strong><br>
                                            <span class="text-primary"><?php echo htmlspecialchars($loai_xe['MaLoaiXe']); ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Số ghế:</strong><br>
                                            <span class="badge bg-info"><?php echo $loai_xe['SoGhe']; ?> ghế</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Phân loại:</strong><br>
                                            <?php
                                            $so_ghe = $loai_xe['SoGhe'];
                                            if ($so_ghe <= 16) {
                                                echo '<span class="badge bg-success">Xe nhỏ</span>';
                                            } elseif ($so_ghe <= 29) {
                                                echo '<span class="badge bg-warning text-dark">Xe trung</span>';
                                            } else {
                                                echo '<span class="badge bg-primary">Xe lớn</span>';
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
                            <a href="view.php?id=<?php echo $loai_xe['MaLoaiXe']; ?>" class="btn btn-info">
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
