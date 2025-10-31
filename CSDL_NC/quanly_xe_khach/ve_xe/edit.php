<?php
$page_title = "Chỉnh sửa vé xe";
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

// Lấy thông tin vé xe hiện tại
try {
    $query = "SELECT vx.*, 
                     cx.GioDi, cx.GioDen, cx.TrangThai,
                     td.DiemDau, td.DiemCuoi, td.DoDai,
                     xk.MaXe, lx.TenLoaiXe, lx.SoGhe,
                     bg.GiaVeNiemYet
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

    // Kiểm tra có thể chỉnh sửa không
    if ($ve_xe['TrangThai'] == 2) {
        $error = "Không thể chỉnh sửa vé của chuyến xe đã hoàn thành!";
    } elseif (strtotime($ve_xe['GioDi']) <= time()) {
        $error = "Không thể chỉnh sửa vé của chuyến xe đã khởi hành!";
    }
} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $vi_tri = trim(strtoupper($_POST['vi_tri']));
    $ten_hanh_khach = trim($_POST['ten_hanh_khach']);
    $sdt_hanh_khach = trim($_POST['sdt_hanh_khach']);
    $gia_ve_thuc_te = floatval($_POST['gia_ve_thuc_te']);

    // Validation
    if (empty($vi_tri) || empty($ten_hanh_khach) || empty($sdt_hanh_khach)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } elseif ($gia_ve_thuc_te <= 0 || $gia_ve_thuc_te > 10000000) {
        $error = "Giá vé phải từ 1đ đến 10,000,000đ!";
    } elseif (!preg_match('/^(84|0[3|5|7|8|9])+([0-9]{8})$/', str_replace([' ', '-'], '', $sdt_hanh_khach))) {
        $error = "Số điện thoại không hợp lệ!";
    } else {
        try {
            // Kiểm tra trùng vị trí (trừ vé hiện tại)
            if ($vi_tri != $ve_xe['ViTri']) {
                $check_seat_query = "SELECT COUNT(*) FROM ve_xe WHERE MaChuyenXe = ? AND ViTri = ? AND MaVe != ?";
                $check_seat_stmt = $db->prepare($check_seat_query);
                $check_seat_stmt->execute([$ve_xe['MaChuyenXe'], $vi_tri, $id]);

                if ($check_seat_stmt->fetchColumn() > 0) {
                    $error = "Vị trí $vi_tri đã được đặt trong chuyến xe này!";
                }
            }

            if (!$error) {
                $query = "UPDATE ve_xe SET ViTri = ?, TenHanhKhach = ?, SDTHanhKhach = ?, GiaVeThucTe = ? WHERE MaVe = ?";
                $stmt = $db->prepare($query);

                if ($stmt->execute([$vi_tri, $ten_hanh_khach, str_replace([' ', '-'], '', $sdt_hanh_khach), $gia_ve_thuc_te, $id])) {
                    $success = "Cập nhật vé thành công!";
                    // Cập nhật lại thông tin hiển thị
                    $ve_xe['ViTri'] = $vi_tri;
                    $ve_xe['TenHanhKhach'] = $ten_hanh_khach;
                    $ve_xe['SDTHanhKhach'] = str_replace([' ', '-'], '', $sdt_hanh_khach);
                    $ve_xe['GiaVeThucTe'] = $gia_ve_thuc_te;
                } else {
                    $error = "Có lỗi xảy ra khi cập nhật vé!";
                }
            }
        } catch(PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
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
                    Chỉnh sửa vé xe
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Vé xe</a></li>
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

                <?php if ($ve_xe && strtotime($ve_xe['GioDi']) > time() && $ve_xe['TrangThai'] == 1): ?>
                    <!-- Thông tin cố định -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle text-info me-2"></i>Thông tin cố định
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Mã vé:</strong><br>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($ve_xe['MaVe']); ?></span><br><br>
                                        <strong>Chuyến xe:</strong><br>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($ve_xe['MaChuyenXe']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ve_xe['MaXe'] . ' - ' . $ve_xe['TenLoaiXe']); ?></small>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Tuyến đường:</strong><br>
                                        <span class="fw-bold"><?php echo htmlspecialchars($ve_xe['DiemDau']); ?></span>
                                        <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                        <span class="fw-bold"><?php echo htmlspecialchars($ve_xe['DiemCuoi']); ?></span><br>
                                        <small class="text-muted"><?php echo number_format($ve_xe['DoDai'], 1); ?> km</small><br><br>
                                        <strong>Thời gian:</strong><br>
                                        <small>
                                            <strong>Đi:</strong> <?php echo date('d/m/Y H:i', strtotime($ve_xe['GioDi'])); ?><br>
                                            <strong>Đến:</strong> <?php echo date('d/m/Y H:i', strtotime($ve_xe['GioDen'])); ?>
                                        </small>
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
                                    <i class="fas fa-chair me-1"></i>Vị trí ghế <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="vi_tri"
                                       value="<?php echo isset($_POST['vi_tri']) ? htmlspecialchars($_POST['vi_tri']) : htmlspecialchars($ve_xe['ViTri']); ?>"
                                       style="text-transform: uppercase;"
                                       maxlength="10"
                                       required>
                                <div class="form-text">Vị trí hiện tại: <?php echo htmlspecialchars($ve_xe['ViTri']); ?></div>
                                <div class="invalid-feedback">Vui lòng nhập vị trí ghế</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Giá vé thực tế <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="gia_ve_thuc_te"
                                           value="<?php echo isset($_POST['gia_ve_thuc_te']) ? $_POST['gia_ve_thuc_te'] : $ve_xe['GiaVeThucTe']; ?>"
                                           min="1000"
                                           max="10000000"
                                           step="1000"
                                           required>
                                    <span class="input-group-text">đ</span>
                                </div>
                                <?php if ($ve_xe['GiaVeNiemYet']): ?>
                                    <div class="form-text">
                                        Giá niêm yết: <?php echo number_format($ve_xe['GiaVeNiemYet'], 0, ',', '.'); ?>đ
                                    </div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Vui lòng nhập giá vé hợp lệ</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-user me-1"></i>Tên hành khách <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ten_hanh_khach"
                                   value="<?php echo isset($_POST['ten_hanh_khach']) ? htmlspecialchars($_POST['ten_hanh_khach']) : htmlspecialchars($ve_xe['TenHanhKhach']); ?>"
                                   maxlength="100"
                                   required>
                            <div class="invalid-feedback">Vui lòng nhập tên hành khách</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-phone me-1"></i>Số điện thoại <span class="text-danger">*</span>
                            </label>
                            <input type="tel"
                                   class="form-control"
                                   name="sdt_hanh_khach"
                                   value="<?php echo isset($_POST['sdt_hanh_khach']) ? htmlspecialchars($_POST['sdt_hanh_khach']) : htmlspecialchars($ve_xe['SDTHanhKhach']); ?>"
                                   pattern="^(84|0[3|5|7|8|9])+([0-9]{8})$"
                                   maxlength="15"
                                   required>
                            <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ</div>
                        </div>

                        <!-- So sánh thay đổi -->
                        <div class="mb-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>So sánh thay đổi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Thông tin hiện tại:</strong><br>
                                            <small class="text-muted">
                                                Vị trí: <?php echo htmlspecialchars($ve_xe['ViTri']); ?><br>
                                                Khách: <?php echo htmlspecialchars($ve_xe['TenHanhKhach']); ?><br>
                                                SĐT: <?php echo htmlspecialchars($ve_xe['SDTHanhKhach']); ?><br>
                                                Giá vé: <?php echo number_format($ve_xe['GiaVeThucTe'], 0, ',', '.'); ?>đ
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Sẽ thay đổi thành:</strong><br>
                                            <span id="change-preview" class="small text-success">
                                            Sẽ cập nhật khi thay đổi
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thời gian còn lại -->
                        <div class="mb-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h6 class="text-info">
                                        <i class="fas fa-clock me-2"></i>Thời gian còn lại đến giờ khởi hành
                                    </h6>
                                    <div id="countdown" class="h4 text-primary">
                                        <!-- Sẽ được cập nhật bằng JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <a href="view.php?id=<?php echo $ve_xe['MaVe']; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Xem chi tiết
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Cập nhật
                            </button>
                        </div>
                    </form>

                    <script>
                        // Đếm ngược thời gian
                        const departureTime = new Date('<?php echo $ve_xe['GioDi']; ?>').getTime();

                        function updateCountdown() {
                            const now = new Date().getTime();
                            const distance = departureTime - now;

                            if (distance > 0) {
                                const hours = Math.floor(distance / (1000 * 60 * 60));
                                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                                document.getElementById('countdown').innerHTML =
                                    hours + "h " + minutes + "m " + seconds + "s";

                                if (hours < 1) {
                                    document.getElementById('countdown').className = 'h4 text-danger';
                                }
                            } else {
                                document.getElementById('countdown').innerHTML = 'Đã khởi hành';
                                document.getElementById('countdown').className = 'h4 text-danger';
                                // Disable form
                                document.querySelector('form').style.display = 'none';
                                location.reload();
                            }
                        }

                        // Cập nhật mỗi giây
                        setInterval(updateCountdown, 1000);
                        updateCountdown();

                        // Cập nhật preview thay đổi
                        function updateChangePreview() {
                            const newViTri = document.querySelector('input[name="vi_tri"]').value;
                            const newTen = document.querySelector('input[name="ten_hanh_khach"]').value;
                            const newSDT = document.querySelector('input[name="sdt_hanh_khach"]').value;
                            const newGia = document.querySelector('input[name="gia_ve_thuc_te"]').value;

                            const preview = document.getElementById('change-preview');

                            if (newViTri && newTen && newSDT && newGia) {
                                preview.innerHTML = `
                            Vị trí: ${newViTri}<br>
                            Khách: ${newTen}<br>
                            SĐT: ${newSDT}<br>
                            Giá vé: ${parseInt(newGia).toLocaleString('vi-VN')}đ
                        `;
                            } else {
                                preview.innerHTML = 'Sẽ cập nhật khi thay đổi';
                            }
                        }

                        // Lắng nghe thay đổi
                        document.querySelectorAll('input').forEach(input => {
                            input.addEventListener('input', updateChangePreview);
                        });

                        // Khởi tạo
                        updateChangePreview();
                    </script>

                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không thể chỉnh sửa vé này. Chuyến xe đã khởi hành hoặc hoàn thành.
                    </div>
                    <div class="text-center">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
