<?php
$page_title = "Bán vé mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy danh sách chuyến xe chưa khởi hành
try {
    $query_chuyen = "SELECT cx.*, 
                            td.DiemDau, td.DiemCuoi, td.DoDai,
                            xk.MaXe, lx.TenLoaiXe, lx.SoGhe,
                            bg.GiaVeNiemYet,
                            (lx.SoGhe - 2) as SoGheToiDa,
                            COUNT(vx.MaVe) as SoVeDaBan
                     FROM chuyen_xe cx
                     LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
                     LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
                     LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
                     LEFT JOIN bang_gia bg ON (cx.MaTuyenDuong = bg.MaTuyenDuong 
                                              AND xk.MaLoaiXe = bg.MaLoaiXe 
                                              AND bg.NgayBatDau <= cx.GioDi 
                                              AND (bg.NgayKetThuc IS NULL OR bg.NgayKetThuc >= cx.GioDi))
                     LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
                     WHERE cx.TrangThai = 1 AND cx.GioDi > DATE_ADD(NOW(), INTERVAL 1 HOUR)
                     GROUP BY cx.MaChuyenXe
                     HAVING SoVeDaBan < SoGheToiDa
                     ORDER BY cx.GioDi";
    $stmt_chuyen = $db->prepare($query_chuyen);
    $stmt_chuyen->execute();
    $chuyen_xe_list = $stmt_chuyen->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy dữ liệu: " . $e->getMessage();
}

if ($_POST && !$error) {
    $ma_ve = trim(strtoupper($_POST['ma_ve']));
    $ma_chuyen_xe = trim($_POST['ma_chuyen_xe']);
    $vi_tri = trim(strtoupper($_POST['vi_tri']));
    $ten_hanh_khach = trim($_POST['ten_hanh_khach']);
    $sdt_hanh_khach = trim($_POST['sdt_hanh_khach']);
    $gia_ve_thuc_te = floatval($_POST['gia_ve_thuc_te']);

    // Validation
    if (empty($ma_ve) || empty($ma_chuyen_xe) || empty($vi_tri) || empty($ten_hanh_khach) || empty($sdt_hanh_khach)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } elseif ($gia_ve_thuc_te <= 0 || $gia_ve_thuc_te > 10000000) {
        $error = "Giá vé phải từ 1đ đến 10,000,000đ!";
    } elseif (!preg_match('/^(84|0[3|5|7|8|9])+([0-9]{8})$/', str_replace([' ', '-'], '', $sdt_hanh_khach))) {
        $error = "Số điện thoại không hợp lệ!";
    } else {
        try {
            // Kiểm tra trùng mã vé
            $check_query = "SELECT COUNT(*) FROM ve_xe WHERE MaVe = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$ma_ve]);

            if ($check_stmt->fetchColumn() > 0) {
                $error = "Mã vé đã tồn tại!";
            } else {
                // Kiểm tra trùng vị trí trong chuyến xe
                $check_seat_query = "SELECT COUNT(*) FROM ve_xe WHERE MaChuyenXe = ? AND ViTri = ?";
                $check_seat_stmt = $db->prepare($check_seat_query);
                $check_seat_stmt->execute([$ma_chuyen_xe, $vi_tri]);

                if ($check_seat_stmt->fetchColumn() > 0) {
                    $error = "Vị trí $vi_tri đã được đặt trong chuyến xe này!";
                } else {
                    // Kiểm tra chuyến xe còn chỗ không
                    $check_capacity_query = "SELECT lx.SoGhe, COUNT(vx.MaVe) as SoVeDaBan
                                           FROM chuyen_xe cx
                                           LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
                                           LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
                                           LEFT JOIN ve_xe vx ON cx.MaChuyenXe = vx.MaChuyenXe
                                           WHERE cx.MaChuyenXe = ?
                                           GROUP BY cx.MaChuyenXe";
                    $check_capacity_stmt = $db->prepare($check_capacity_query);
                    $check_capacity_stmt->execute([$ma_chuyen_xe]);
                    $capacity_info = $check_capacity_stmt->fetch(PDO::FETCH_ASSOC);

                    $so_ghe_toi_da = $capacity_info['SoGhe'] - 2; // Trừ 2 ghế tài xế
                    if ($capacity_info['SoVeDaBan'] >= $so_ghe_toi_da) {
                        $error = "Chuyến xe đã hết chỗ!";
                    } else {
                        $query = "INSERT INTO ve_xe (MaVe, MaChuyenXe, ViTri, TenHanhKhach, SDTHanhKhach, GiaVeThucTe) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);

                        if ($stmt->execute([$ma_ve, $ma_chuyen_xe, $vi_tri, $ten_hanh_khach, str_replace([' ', '-'], '', $sdt_hanh_khach), $gia_ve_thuc_te])) {
                            $success = "Bán vé thành công!";
                            $_POST = array();
                        } else {
                            $error = "Có lỗi xảy ra khi bán vé!";
                        }
                    }
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
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Bán vé mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Vé xe</a></li>
                        <li class="breadcrumb-item active">Bán vé mới</li>
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

                <?php if (empty($chuyen_xe_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có chuyến xe nào có sẵn</h5>
                        <p class="text-muted mb-4">
                            Hiện tại không có chuyến xe nào đang bán vé.<br>
                            Vui lòng kiểm tra lại sau hoặc tạo chuyến xe mới.
                        </p>
                        <a href="../chuyen_xe/create.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-2"></i>Tạo chuyến xe
                        </a>
                        <a href="../chuyen_xe/index.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Xem chuyến xe
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-ticket-alt me-1"></i>Mã vé <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="ma_ve"
                                       value="<?php echo isset($_POST['ma_ve']) ? htmlspecialchars($_POST['ma_ve']) : 'VE' . date('YmdHis'); ?>"
                                       placeholder="VD: VE20250101120000"
                                       style="text-transform: uppercase;"
                                       maxlength="20"
                                       required>
                                <div class="form-text">Mã tự động dựa trên thời gian</div>
                                <div class="invalid-feedback">Vui lòng nhập mã vé</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-route me-1"></i>Chuyến xe <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="ma_chuyen_xe" id="chuyen-select" required>
                                    <option value="">-- Chọn chuyến xe --</option>
                                    <?php foreach ($chuyen_xe_list as $chuyen): ?>
                                        <option value="<?php echo $chuyen['MaChuyenXe']; ?>"
                                                data-price="<?php echo $chuyen['GiaVeNiemYet']; ?>"
                                                data-available="<?php echo $chuyen['SoGheToiDa'] - $chuyen['SoVeDaBan']; ?>"
                                                data-departure="<?php echo $chuyen['GioDi']; ?>"
                                            <?php echo (isset($_POST['ma_chuyen_xe']) && $_POST['ma_chuyen_xe'] == $chuyen['MaChuyenXe']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($chuyen['MaChuyenXe'] . ' - ' . $chuyen['DiemDau'] . ' → ' . $chuyen['DiemCuoi'] . ' (' . date('d/m H:i', strtotime($chuyen['GioDi'])) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn chuyến xe</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-chair me-1"></i>Vị trí ghế <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="vi_tri"
                                       value="<?php echo isset($_POST['vi_tri']) ? htmlspecialchars($_POST['vi_tri']) : ''; ?>"
                                       placeholder="VD: A01, B12, C05"
                                       style="text-transform: uppercase;"
                                       maxlength="10"
                                       required>
                                <div class="form-text">Mã ghế theo sơ đồ xe</div>
                                <div class="invalid-feedback">Vui lòng nhập vị trí ghế</div>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user me-1"></i>Tên hành khách <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="ten_hanh_khach"
                                       value="<?php echo isset($_POST['ten_hanh_khach']) ? htmlspecialchars($_POST['ten_hanh_khach']) : ''; ?>"
                                       placeholder="VD: Nguyễn Văn An"
                                       maxlength="100"
                                       required>
                                <div class="invalid-feedback">Vui lòng nhập tên hành khách</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-phone me-1"></i>Số điện thoại <span class="text-danger">*</span>
                                </label>
                                <input type="tel"
                                       class="form-control"
                                       name="sdt_hanh_khach"
                                       value="<?php echo isset($_POST['sdt_hanh_khach']) ? htmlspecialchars($_POST['sdt_hanh_khach']) : ''; ?>"
                                       placeholder="VD: 0987654321"
                                       pattern="^(84|0[3|5|7|8|9])+([0-9]{8})$"
                                       maxlength="15"
                                       required>
                                <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Giá vé thực tế <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="gia_ve_thuc_te"
                                           id="gia-ve"
                                           value="<?php echo isset($_POST['gia_ve_thuc_te']) ? $_POST['gia_ve_thuc_te'] : ''; ?>"
                                           min="1000"
                                           max="10000000"
                                           step="1000"
                                           placeholder="VD: 150000"
                                           required>
                                    <span class="input-group-text">đ</span>
                                </div>
                                <div class="form-text">Sẽ tự động điền theo giá niêm yết</div>
                                <div class="invalid-feedback">Vui lòng nhập giá vé hợp lệ</div>
                            </div>
                        </div>

                        <!-- Thông tin chuyến xe -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-info">
                                        <i class="fas fa-info-circle me-2"></i>Thông tin chuyến xe
                                    </h6>
                                    <div class="row" id="trip-info">
                                        <div class="col-12">
                                            <small class="text-muted">Chọn chuyến xe để xem thông tin chi tiết</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hướng dẫn bán vé -->
                        <div class="mb-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn bán vé</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Quy định:</strong>
                                            <ul class="mb-0 small">
                                                <li>Bán vé trước giờ khởi hành tối thiểu 1 giờ</li>
                                                <li>Kiểm tra kỹ thông tin hành khách</li>
                                                <li>Mỗi vị trí chỉ bán cho 1 hành khách</li>
                                                <li>Giá vé có thể điều chỉnh theo chính sách</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Lưu ý:</strong>
                                            <ul class="mb-0 small">
                                                <li>Vị trí ghế theo sơ đồ xe cụ thể</li>
                                                <li>Số điện thoại để liên hệ khi cần</li>
                                                <li>Kiểm tra tình trạng xe và tài xế</li>
                                                <li>In vé hoặc gửi thông tin qua SMS</li>
                                            </ul>
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
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>Làm mới
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-ticket-alt me-2"></i>Bán vé
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Cập nhật thông tin khi chọn chuyến xe
    document.getElementById('chuyen-select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const tripInfo = document.getElementById('trip-info');
        const giaVeInput = document.getElementById('gia-ve');

        if (selectedOption.value) {
            const price = selectedOption.getAttribute('data-price');
            const available = selectedOption.getAttribute('data-available');
            const departure = selectedOption.getAttribute('data-departure');

            const departureDate = new Date(departure);
            const now = new Date();
            const hoursLeft = Math.max(0, (departureDate - now) / (1000 * 60 * 60));

            tripInfo.innerHTML = `
            <div class="col-md-3">
                <strong>Giá niêm yết:</strong><br>
                <span class="text-success fw-bold">${price ? parseInt(price).toLocaleString('vi-VN') + 'đ' : 'Chưa có'}</span>
            </div>
            <div class="col-md-3">
                <strong>Chỗ trống:</strong><br>
                <span class="text-info fw-bold">${available} ghế</span>
            </div>
            <div class="col-md-3">
                <strong>Khởi hành:</strong><br>
                <span class="text-warning fw-bold">${departureDate.toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            })}</span>
            </div>
            <div class="col-md-3">
                <strong>Thời gian còn lại:</strong><br>
                <span class="text-primary fw-bold">${Math.floor(hoursLeft)}h ${Math.floor((hoursLeft % 1) * 60)}p</span>
            </div>
        `;

            // Tự động điền giá vé
            if (price && !giaVeInput.value) {
                giaVeInput.value = parseInt(price);
            }

            // Cảnh báo nếu sắp hết thời gian
            if (hoursLeft < 2) {
                tripInfo.innerHTML += `
                <div class="col-12 mt-2">
                    <div class="alert alert-warning alert-sm">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Lưu ý:</strong> Chuyến xe sắp khởi hành, vui lòng xác nhận thông tin trước khi bán vé.
                    </div>
                </div>
            `;
            }

            if (parseInt(available) <= 5) {
                tripInfo.innerHTML += `
                <div class="col-12 mt-2">
                    <div class="alert alert-info alert-sm">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Thông báo:</strong> Chỉ còn ${available} chỗ trống trên chuyến xe này.
                    </div>
                </div>
            `;
            }
        } else {
            tripInfo.innerHTML = '<div class="col-12"><small class="text-muted">Chọn chuyến xe để xem thông tin chi tiết</small></div>';
            giaVeInput.value = '';
        }
    });

    // Tự động tạo vị trí ghế
    document.querySelector('input[name="vi_tri"]').addEventListener('focus', function() {
        if (!this.value) {
            const timestamp = Date.now().toString().slice(-4);
            this.value = 'A' + timestamp.slice(-2);
        }
    });

    // Validate số điện thoại
    document.querySelector('input[name="sdt_hanh_khach"]').addEventListener('input', function() {
        const phone = this.value.replace(/[^0-9]/g, '');
        if (phone.length === 10 && phone.startsWith('0')) {
            this.setCustomValidity('');
        } else if (phone.length === 11 && phone.startsWith('84')) {
            this.setCustomValidity('');
        } else {
            this.setCustomValidity('Số điện thoại không đúng định dạng');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
