<?php
$page_title = "Tạo phân công mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy danh sách chuyến xe chưa khởi hành và tài xế
try {
    $query_chuyen = "SELECT cx.*, td.DiemDau, td.DiemCuoi, td.DoDai,
                            xk.MaXe, lx.TenLoaiXe, lx.SoGhe
                     FROM chuyen_xe cx
                     LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
                     LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
                     LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
                     WHERE cx.TrangThai = 1 AND cx.GioDi > NOW()
                     ORDER BY cx.GioDi";
    $stmt_chuyen = $db->prepare($query_chuyen);
    $stmt_chuyen->execute();
    $chuyen_xe_list = $stmt_chuyen->fetchAll(PDO::FETCH_ASSOC);

    $query_tai_xe = "SELECT * FROM tai_xe ORDER BY HoTen";
    $stmt_tai_xe = $db->prepare($query_tai_xe);
    $stmt_tai_xe->execute();
    $tai_xe_list = $stmt_tai_xe->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy dữ liệu: " . $e->getMessage();
}

if ($_POST && !$error) {
    $ma_chuyen_xe = trim($_POST['ma_chuyen_xe']);
    $ma_tai_xe = trim($_POST['ma_tai_xe']);
    $vai_tro = intval($_POST['vai_tro']);
    $thu_lao = floatval($_POST['thu_lao']);

    // Validation
    if (empty($ma_chuyen_xe) || empty($ma_tai_xe) || $vai_tro < 1 || $vai_tro > 2 || $thu_lao <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin hợp lệ!";
    } elseif ($thu_lao > 10000000) {
        $error = "Thu lao không được vượt quá 10,000,000đ!";
    } else {
        try {
            // Kiểm tra trùng lặp
            $check_query = "SELECT COUNT(*) FROM phan_cong WHERE MaChuyenXe = ? AND MaTaiXe = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$ma_chuyen_xe, $ma_tai_xe]);

            if ($check_stmt->fetchColumn() > 0) {
                $error = "Tài xế này đã được phân công cho chuyến xe này!";
            } else {
                // Kiểm tra xung đột lịch trình tài xế
                $conflict_query = "SELECT COUNT(*) FROM phan_cong pc
                                  LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
                                  LEFT JOIN chuyen_xe cx2 ON cx2.MaChuyenXe = ?
                                  WHERE pc.MaTaiXe = ? AND cx.TrangThai = 1
                                  AND (
                                      (cx.GioDi <= cx2.GioDen AND cx.GioDen >= cx2.GioDi)
                                  )";
                $conflict_stmt = $db->prepare($conflict_query);
                $conflict_stmt->execute([$ma_chuyen_xe, $ma_tai_xe]);

                if ($conflict_stmt->fetchColumn() > 0) {
                    $error = "Tài xế này đã có lịch trình trùng với thời gian chuyến xe!";
                } else {
                    // Kiểm tra số lượng tài xế chính (chỉ được có 1)
                    if ($vai_tro == 1) {
                        $check_main_query = "SELECT COUNT(*) FROM phan_cong WHERE MaChuyenXe = ? AND VaiTro = 1";
                        $check_main_stmt = $db->prepare($check_main_query);
                        $check_main_stmt->execute([$ma_chuyen_xe]);

                        if ($check_main_stmt->fetchColumn() > 0) {
                            $error = "Chuyến xe này đã có tài xế chính!";
                        }
                    }

                    if (!$error) {
                        $query = "INSERT INTO phan_cong (MaChuyenXe, MaTaiXe, VaiTro, ThuLao) VALUES (?, ?, ?, ?)";
                        $stmt = $db->prepare($query);

                        if ($stmt->execute([$ma_chuyen_xe, $ma_tai_xe, $vai_tro, $thu_lao])) {
                            $success = "Tạo phân công thành công!";
                            $_POST = array();
                        } else {
                            $error = "Có lỗi xảy ra khi tạo phân công!";
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
                    Tạo phân công mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Phân công</a></li>
                        <li class="breadcrumb-item active">Tạo mới</li>
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

                <!-- Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-route me-1"></i>Chuyến xe <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="ma_chuyen_xe" id="chuyen-select" required>
                                <option value="">-- Chọn chuyến xe --</option>
                                <?php foreach ($chuyen_xe_list as $chuyen): ?>
                                    <option value="<?php echo $chuyen['MaChuyenXe']; ?>"
                                            data-distance="<?php echo $chuyen['DoDai']; ?>"
                                            data-departure="<?php echo $chuyen['GioDi']; ?>"
                                            data-arrival="<?php echo $chuyen['GioDen']; ?>"
                                        <?php echo (isset($_POST['ma_chuyen_xe']) && $_POST['ma_chuyen_xe'] == $chuyen['MaChuyenXe']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($chuyen['MaChuyenXe'] . ' - ' . $chuyen['DiemDau'] . ' → ' . $chuyen['DiemCuoi'] . ' (' . date('d/m H:i', strtotime($chuyen['GioDi'])) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn chuyến xe</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-user-tie me-1"></i>Tài xế <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="ma_tai_xe" id="tai-xe-select" required>
                                <option value="">-- Chọn tài xế --</option>
                                <?php foreach ($tai_xe_list as $tai_xe): ?>
                                    <option value="<?php echo $tai_xe['MaTaiXe']; ?>"
                                        <?php echo (isset($_POST['ma_tai_xe']) && $_POST['ma_tai_xe'] == $tai_xe['MaTaiXe']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tai_xe['MaTaiXe'] . ' - ' . $tai_xe['HoTen'] . ' (' . $tai_xe['SDT'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn tài xế</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-user-cog me-1"></i>Vai trò <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="vai_tro" id="vai-tro-select" required>
                                <option value="">-- Chọn vai trò --</option>
                                <option value="1" <?php echo (isset($_POST['vai_tro']) && $_POST['vai_tro'] == '1') ? 'selected' : ''; ?>>
                                    Tài xế chính
                                </option>
                                <option value="2" <?php echo (isset($_POST['vai_tro']) && $_POST['vai_tro'] == '2') ? 'selected' : ''; ?>>
                                    Lái phụ
                                </option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn vai trò</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>Thu lao <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number"
                                       class="form-control"
                                       name="thu_lao"
                                       id="thu-lao"
                                       value="<?php echo isset($_POST['thu_lao']) ? $_POST['thu_lao'] : ''; ?>"
                                       min="50000"
                                       max="10000000"
                                       step="10000"
                                       placeholder="VD: 500000"
                                       required>
                                <span class="input-group-text">đ</span>
                            </div>
                            <div class="form-text">Từ 50,000đ đến 10,000,000đ</div>
                            <div class="invalid-feedback">Vui lòng nhập thu lao hợp lệ</div>
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

                    <!-- Gợi ý thu lao -->
                    <div class="mb-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Gợi ý thu lao</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Theo vai trò:</strong>
                                        <ul class="mb-0 small">
                                            <li>Tài xế chính: 15,000-25,000đ/100km</li>
                                            <li>Lái phụ: 10,000-18,000đ/100km</li>
                                            <li>Chuyến dài (>500km): +20-30% phụ phí</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Theo độ khó:</strong>
                                        <ul class="mb-0 small">
                                            <li>Đường cao tốc: Mức cơ bản</li>
                                            <li>Đường đèo dốc: +30-50%</li>
                                            <li>Chuyến đêm: +20-30%</li>
                                            <li>Ngày lễ/Tết: +50-100%</li>
                                        </ul>
                                    </div>
                                </div>
                                <div id="salary-suggestion" class="mt-3 p-2 bg-light rounded">
                                    <small class="text-muted">Chọn chuyến xe và vai trò để xem gợi ý thu lao</small>
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
                            <i class="fas fa-save me-2"></i>Tạo phân công
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Cập nhật thông tin khi chọn chuyến xe
    document.getElementById('chuyen-select').addEventListener('change', function() {
        updateTripInfo();
        updateSalarySuggestion();
    });

    document.getElementById('vai-tro-select').addEventListener('change', function() {
        updateSalarySuggestion();
    });

    function updateTripInfo() {
        const chuyenSelect = document.getElementById('chuyen-select');
        const selectedOption = chuyenSelect.options[chuyenSelect.selectedIndex];
        const tripInfo = document.getElementById('trip-info');

        if (selectedOption.value) {
            const distance = selectedOption.getAttribute('data-distance');
            const departure = selectedOption.getAttribute('data-departure');
            const arrival = selectedOption.getAttribute('data-arrival');

            const departureDate = new Date(departure);
            const arrivalDate = new Date(arrival);
            const duration = (arrivalDate - departureDate) / (1000 * 60 * 60); // hours

            tripInfo.innerHTML = `
            <div class="col-md-4">
                <strong>Khoảng cách:</strong><br>
                <span class="text-primary">${parseFloat(distance).toLocaleString('vi-VN')} km</span>
            </div>
            <div class="col-md-4">
                <strong>Thời gian:</strong><br>
                <span class="text-info">${duration.toFixed(1)} giờ</span>
            </div>
            <div class="col-md-4">
                <strong>Khởi hành:</strong><br>
                <span class="text-success">${departureDate.toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            })}</span>
            </div>
        `;
        } else {
            tripInfo.innerHTML = '<div class="col-12"><small class="text-muted">Chọn chuyến xe để xem thông tin chi tiết</small></div>';
        }
    }

    function updateSalarySuggestion() {
        const chuyenSelect = document.getElementById('chuyen-select');
        const vaiTroSelect = document.getElementById('vai-tro-select');
        const thuLaoInput = document.getElementById('thu-lao');
        const salarySuggestion = document.getElementById('salary-suggestion');

        const selectedChuyen = chuyenSelect.options[chuyenSelect.selectedIndex];
        const selectedVaiTro = vaiTroSelect.value;

        if (selectedChuyen.value && selectedVaiTro) {
            const distance = parseFloat(selectedChuyen.getAttribute('data-distance'));
            const departure = new Date(selectedChuyen.getAttribute('data-departure'));
            const isNightTrip = departure.getHours() < 6 || departure.getHours() >= 22;

            // Tính thu lao gợi ý
            let baseRate = selectedVaiTro == '1' ? 200 : 140; // đ/km cho tài xế chính/lái phụ
            let suggestedSalary = distance * baseRate;

            // Điều chỉnh theo thời gian
            if (isNightTrip) {
                suggestedSalary *= 1.3; // +30% cho chuyến đêm
            }

            // Điều chỉnh theo khoảng cách
            if (distance > 500) {
                suggestedSalary *= 1.2; // +20% cho chuyến dài
            }

            // Làm tròn
            suggestedSalary = Math.round(suggestedSalary / 10000) * 10000;

            const minSalary = Math.round(suggestedSalary * 0.8);
            const maxSalary = Math.round(suggestedSalary * 1.2);

            salarySuggestion.innerHTML = `
            <strong>Gợi ý thu lao:</strong>
            <span class="text-success fw-bold">${suggestedSalary.toLocaleString('vi-VN')}đ</span>
            <small class="text-muted">(${minSalary.toLocaleString('vi-VN')}đ - ${maxSalary.toLocaleString('vi-VN')}đ)</small>
            ${isNightTrip ? '<span class="badge bg-warning text-dark ms-2">Chuyến đêm</span>' : ''}
            ${distance > 500 ? '<span class="badge bg-info ms-1">Chuyến dài</span>' : ''}
        `;

            // Tự động điền thu lao nếu chưa có giá trị
            if (!thuLaoInput.value) {
                thuLaoInput.value = suggestedSalary;
            }
        } else {
            salarySuggestion.innerHTML = '<small class="text-muted">Chọn chuyến xe và vai trò để xem gợi ý thu lao</small>';
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
