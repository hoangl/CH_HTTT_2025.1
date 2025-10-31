<?php
$page_title = "Tạo chuyến xe mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy danh sách tuyến đường và xe khách
try {
    $query_tuyen = "SELECT * FROM tuyen_duong ORDER BY MaTuyenDuong";
    $stmt_tuyen = $db->prepare($query_tuyen);
    $stmt_tuyen->execute();
    $tuyen_duong_list = $stmt_tuyen->fetchAll(PDO::FETCH_ASSOC);

    $query_xe = "SELECT xk.*, lx.TenLoaiXe, lx.SoGhe
                 FROM xe_khach xk 
                 LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
                 WHERE xk.SoNgayBDConLai > 0 AND xk.HanDangKiem > CURDATE()
                 ORDER BY xk.MaXe";
    $stmt_xe = $db->prepare($query_xe);
    $stmt_xe->execute();
    $xe_khach_list = $stmt_xe->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Lỗi khi lấy dữ liệu: " . $e->getMessage();
}

if ($_POST && !$error) {
    $ma_chuyen_xe = trim(strtoupper($_POST['ma_chuyen_xe']));
    $ma_xe = trim($_POST['ma_xe']);
    $ma_tuyen_duong = trim($_POST['ma_tuyen_duong']);
    $gio_di = $_POST['gio_di'];
    $gio_den = $_POST['gio_den'];
    $trang_thai = intval($_POST['trang_thai']);

    // Validation
    if (empty($ma_chuyen_xe) || empty($ma_xe) || empty($ma_tuyen_duong) || empty($gio_di) || empty($gio_den)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif (strtotime($gio_di) <= time()) {
        $error = "Giờ khởi hành phải sau thời điểm hiện tại!";
    } elseif (strtotime($gio_den) <= strtotime($gio_di)) {
        $error = "Giờ đến phải sau giờ khởi hành!";
    } else {
        // Kiểm tra thời gian hợp lý (ít nhất 1 giờ, không quá 24 giờ)
        $duration_hours = (strtotime($gio_den) - strtotime($gio_di)) / 3600;
        if ($duration_hours < 1) {
            $error = "Thời gian di chuyển phải ít nhất 1 giờ!";
        } elseif ($duration_hours > 24) {
            $error = "Thời gian di chuyển không được quá 24 giờ!";
        } else {
            try {
                // Kiểm tra trùng mã chuyến xe
                $check_query = "SELECT COUNT(*) FROM chuyen_xe WHERE MaChuyenXe = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$ma_chuyen_xe]);

                if ($check_stmt->fetchColumn() > 0) {
                    $error = "Mã chuyến xe đã tồn tại!";
                } else {
                    // Kiểm tra xung đột lịch trình xe
                    $conflict_query = "SELECT COUNT(*) FROM chuyen_xe 
                                      WHERE MaXe = ? AND TrangThai = 1
                                      AND (
                                          (GioDi <= ? AND GioDen >= ?) OR
                                          (GioDi <= ? AND GioDen >= ?) OR
                                          (GioDi >= ? AND GioDen <= ?)
                                      )";
                    $conflict_stmt = $db->prepare($conflict_query);
                    $conflict_stmt->execute([$ma_xe, $gio_di, $gio_di, $gio_den, $gio_den, $gio_di, $gio_den]);

                    if ($conflict_stmt->fetchColumn() > 0) {
                        $error = "Xe này đã có lịch trình trùng với thời gian đã chọn!";
                    } else {
                        $query = "INSERT INTO chuyen_xe (MaChuyenXe, MaXe, MaTuyenDuong, GioDi, GioDen, TrangThai) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);

                        if ($stmt->execute([$ma_chuyen_xe, $ma_xe, $ma_tuyen_duong, $gio_di, $gio_den, $trang_thai])) {
                            $success = "Tạo chuyến xe thành công!";
                            $_POST = array();
                        } else {
                            $error = "Có lỗi xảy ra khi tạo chuyến xe!";
                        }
                    }
                }
            } catch(PDOException $e) {
                $error = "Lỗi: " . $e->getMessage();
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
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    Tạo chuyến xe mới
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Chuyến xe</a></li>
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
                                <i class="fas fa-id-card me-1"></i>Mã chuyến xe <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="ma_chuyen_xe"
                                   value="<?php echo isset($_POST['ma_chuyen_xe']) ? htmlspecialchars($_POST['ma_chuyen_xe']) : 'CX' . date('YmdHi'); ?>"
                                   placeholder="VD: CX202501010800"
                                   style="text-transform: uppercase;"
                                   maxlength="20"
                                   required>
                            <div class="form-text">Mã tự động dựa trên thời gian</div>
                            <div class="invalid-feedback">Vui lòng nhập mã chuyến xe</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Trạng thái <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="trang_thai" required>
                                <option value="">-- Chọn trạng thái --</option>
                                <option value="1" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == '1') ? 'selected' : 'selected'; ?>>
                                    Chờ khởi hành
                                </option>
                                <option value="2" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == '2') ? 'selected' : ''; ?>>
                                    Hoàn thành
                                </option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn trạng thái</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-route me-1"></i>Tuyến đường <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="ma_tuyen_duong" id="tuyen-select" required>
                                <option value="">-- Chọn tuyến đường --</option>
                                <?php foreach ($tuyen_duong_list as $tuyen): ?>
                                    <option value="<?php echo $tuyen['MaTuyenDuong']; ?>"
                                            data-distance="<?php echo $tuyen['DoDai']; ?>"
                                            data-complexity="<?php echo $tuyen['DoPhucTap']; ?>"
                                        <?php echo (isset($_POST['ma_tuyen_duong']) && $_POST['ma_tuyen_duong'] == $tuyen['MaTuyenDuong']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tuyen['MaTuyenDuong'] . ' - ' . $tuyen['DiemDau'] . ' → ' . $tuyen['DiemCuoi'] . ' (' . $tuyen['DoDai'] . 'km)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn tuyến đường</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-bus me-1"></i>Xe khách <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="ma_xe" required>
                                <option value="">-- Chọn xe khách --</option>
                                <?php foreach ($xe_khach_list as $xe): ?>
                                    <option value="<?php echo $xe['MaXe']; ?>"
                                        <?php echo (isset($_POST['ma_xe']) && $_POST['ma_xe'] == $xe['MaXe']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($xe['MaXe'] . ' - ' . $xe['TenLoaiXe'] . ' (' . $xe['SoGhe'] . ' ghế)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn xe khách</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-play me-1 text-success"></i>Giờ khởi hành <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local"
                                   class="form-control"
                                   name="gio_di"
                                   id="gio-di"
                                   value="<?php echo isset($_POST['gio_di']) ? $_POST['gio_di'] : date('Y-m-d\TH:i', strtotime('+2 hours')); ?>"
                                   min="<?php echo date('Y-m-d\TH:i'); ?>"
                                   required>
                            <div class="invalid-feedback">Vui lòng chọn giờ khởi hành</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-stop me-1 text-danger"></i>Giờ đến dự kiến <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local"
                                   class="form-control"
                                   name="gio_den"
                                   id="gio-den"
                                   value="<?php echo isset($_POST['gio_den']) ? $_POST['gio_den'] : ''; ?>"
                                   required>
                            <div class="form-text">Sẽ tự động tính dựa trên tuyến đường</div>
                            <div class="invalid-feedback">Vui lòng chọn giờ đến</div>
                        </div>
                    </div>

                    <!-- Thông tin ước tính -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-calculator me-2"></i>Thông tin ước tính
                                </h6>
                                <div class="row" id="estimate-info">
                                    <div class="col-12">
                                        <small class="text-muted">Chọn tuyến đường để xem thông tin ước tính</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hướng dẫn -->
                    <div class="mb-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn lập lịch</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0 small">
                                    <li>Chọn tuyến đường trước để hệ thống ước tính thời gian di chuyển</li>
                                    <li>Đảm bảo xe khách đã qua bảo dưỡng và còn hạn đăng kiểm</li>
                                    <li>Thời gian khởi hành phải ít nhất 2 giờ từ thời điểm hiện tại</li>
                                    <li>Sau khi tạo chuyến xe, nhớ phân công tài xế trước giờ khởi hành</li>
                                </ul>
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
                            <i class="fas fa-save me-2"></i>Tạo chuyến xe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Tự động tính giờ đến dựa trên tuyến đường và giờ đi
    document.getElementById('tuyen-select').addEventListener('change', function() {
        calculateEstimatedArrival();
        updateEstimateInfo();
    });

    document.getElementById('gio-di').addEventListener('change', function() {
        calculateEstimatedArrival();
    });

    function calculateEstimatedArrival() {
        const tuyenSelect = document.getElementById('tuyen-select');
        const gioDiInput = document.getElementById('gio-di');
        const gioDenInput = document.getElementById('gio-den');

        const selectedOption = tuyenSelect.options[tuyenSelect.selectedIndex];
        const distance = parseFloat(selectedOption.getAttribute('data-distance'));
        const complexity = parseInt(selectedOption.getAttribute('data-complexity'));
        const departureTime = gioDiInput.value;

        if (distance && complexity && departureTime) {
            // Tính tốc độ trung bình dựa trên độ phức tạp
            let averageSpeed;
            switch(complexity) {
                case 1: averageSpeed = 60; break; // Đơn giản
                case 2: averageSpeed = 50; break; // Trung bình
                case 3: averageSpeed = 40; break; // Phức tạp
                default: averageSpeed = 50; break;
            }

            // Tính thời gian di chuyển (giờ)
            const travelTime = distance / averageSpeed;

            // Cộng thêm thời gian nghỉ (15 phút mỗi 100km)
            const restTime = Math.floor(distance / 100) * 0.25;

            const totalTime = travelTime + restTime;

            // Tính giờ đến
            const departureDate = new Date(departureTime);
            const arrivalDate = new Date(departureDate.getTime() + totalTime * 60 * 60 * 1000);

            // Format datetime-local
            const arrivalString = arrivalDate.toISOString().slice(0, 16);
            gioDenInput.value = arrivalString;
        }
    }

    function updateEstimateInfo() {
        const tuyenSelect = document.getElementById('tuyen-select');
        const selectedOption = tuyenSelect.options[tuyenSelect.selectedIndex];
        const distance = parseFloat(selectedOption.getAttribute('data-distance'));
        const complexity = parseInt(selectedOption.getAttribute('data-complexity'));
        const estimateInfo = document.getElementById('estimate-info');

        if (distance && complexity) {
            let averageSpeed;
            let complexityText;
            switch(complexity) {
                case 1:
                    averageSpeed = 60;
                    complexityText = 'Đơn giản (60km/h)';
                    break;
                case 2:
                    averageSpeed = 50;
                    complexityText = 'Trung bình (50km/h)';
                    break;
                case 3:
                    averageSpeed = 40;
                    complexityText = 'Phức tạp (40km/h)';
                    break;
            }

            const travelTime = distance / averageSpeed;
            const restTime = Math.floor(distance / 100) * 0.25;
            const totalTime = travelTime + restTime;

            const hours = Math.floor(totalTime);
            const minutes = Math.round((totalTime - hours) * 60);

            estimateInfo.innerHTML = `
            <div class="col-md-4">
                <strong>Khoảng cách:</strong><br>
                <span class="text-primary">${distance.toLocaleString('vi-VN')} km</span>
            </div>
            <div class="col-md-4">
                <strong>Độ phức tạp:</strong><br>
                <span class="text-info">${complexityText}</span>
            </div>
            <div class="col-md-4">
                <strong>Thời gian ước tính:</strong><br>
                <span class="text-success">${hours}h ${minutes}p</span>
            </div>
        `;
        } else {
            estimateInfo.innerHTML = '<div class="col-12"><small class="text-muted">Chọn tuyến đường để xem thông tin ước tính</small></div>';
        }
    }

    // Validation giờ đến
    document.getElementById('gio-di').addEventListener('change', function() {
        document.getElementById('gio-den').min = this.value;
    });
</script>

<?php include '../includes/footer.php'; ?>