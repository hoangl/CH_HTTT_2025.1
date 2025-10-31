<?php
$page_title = "Tạo chuyến xe mới";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy danh sách tuyến đường, xe khách và tài xế
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

    // Lấy danh sách tài xế available
    $query_tai_xe = "SELECT * FROM tai_xe ORDER BY HoTen";
    $stmt_tai_xe = $db->prepare($query_tai_xe);
    $stmt_tai_xe->execute();
    $tai_xe_list = $stmt_tai_xe->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Lỗi khi lấy dữ liệu: " . $e->getMessage();
}

if ($_POST && !$error) {
    $ma_xe = trim($_POST['ma_xe']);
    $ma_tuyen_duong = trim($_POST['ma_tuyen_duong']);
    $gio_di = $_POST['gio_di'];
    $gio_den = $_POST['gio_den'];
    $trang_thai = 1;
    $tai_xe_chinh = trim($_POST['tai_xe_chinh']);
    $lai_phu = trim($_POST['lai_phu']);

    // Validation
    if (empty($ma_xe) || empty($ma_tuyen_duong) || empty($gio_di) || empty($gio_den) || empty($tai_xe_chinh)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc! (Tài xế chính là bắt buộc)";
    } elseif ($tai_xe_chinh == $lai_phu && !empty($lai_phu)) {
        $error = "Tài xế chính và lái phụ không thể là cùng một người!";
    } elseif (strtotime($gio_di) <= time()) {
        $error = "Giờ khởi hành phải sau thời điểm hiện tại!";
    } elseif (strtotime($gio_den) <= strtotime($gio_di)) {
        $error = "Giờ đến phải sau giờ khởi hành!";
    } else {
        try {
            $db->beginTransaction();

            // 1. Tạo chuyến xe (trigger sẽ tự động tạo MaChuyenXe)
            $query = "INSERT INTO chuyen_xe (MaTuyenDuong, MaXe, GioDi, GioDen, TrangThai) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$ma_tuyen_duong, $ma_xe, $gio_di, $gio_den, $trang_thai]);

            // Lấy MaChuyenXe vừa được tạo tự động
            $query_get_id = "SELECT MaChuyenXe FROM chuyen_xe WHERE MaTuyenDuong = ? AND MaXe = ? AND GioDi = ? ORDER BY MaChuyenXe DESC LIMIT 1";
            $stmt_get_id = $db->prepare($query_get_id);
            $stmt_get_id->execute([$ma_tuyen_duong, $ma_xe, $gio_di]);
            $ma_chuyen_xe = $stmt_get_id->fetchColumn();

            if (!$ma_chuyen_xe) {
                throw new Exception("Không thể tạo chuyến xe!");
            }

            // 2. Kiểm tra xung đột lịch trình tài xế
            $conflict_query = "SELECT COUNT(*) FROM phan_cong pc
                              LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
                              WHERE pc.MaTaiXe IN (?, ?) AND cx.TrangThai = 1
                              AND (
                                  (cx.GioDi <= ? AND cx.GioDen >= ?) OR
                                  (cx.GioDi <= ? AND cx.GioDen >= ?) OR
                                  (cx.GioDi >= ? AND cx.GioDen <= ?)
                              )";
            $params = [$tai_xe_chinh, $lai_phu ?: $tai_xe_chinh, $gio_di, $gio_di, $gio_den, $gio_den, $gio_di, $gio_den];
            $conflict_stmt = $db->prepare($conflict_query);
            $conflict_stmt->execute($params);

            if ($conflict_stmt->fetchColumn() > 0) {
                throw new Exception("Tài xế đã có lịch trình trùng với thời gian này!");
            }

            // 3. Thêm phân công tài xế chính
            $query_pc = "INSERT INTO phan_cong (MaChuyenXe, MaTaiXe, VaiTro, ThuLao) VALUES (?, ?, 1, 0)";
            $stmt_pc = $db->prepare($query_pc);
            $stmt_pc->execute([$ma_chuyen_xe, $tai_xe_chinh]);

            // 4. Thêm lái phụ nếu có
            if (!empty($lai_phu)) {
                $stmt_pc->execute([$ma_chuyen_xe, $lai_phu, 2]);
            }

            $db->commit();
            $success = "Tạo chuyến xe và phân công tài xế thành công! Mã chuyến xe: " . $ma_chuyen_xe;
            $_POST = array();

        } catch (Exception $e) {
            $db->rollBack();
            $error = "Lỗi: " . $e->getMessage();
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Lỗi database: " . $e->getMessage();
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
                    Tạo chuyến xe và phân công tài xế
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
                    <!-- Thông tin chuyến xe -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-black">
                            <h6 class="mb-0"><i class="fas fa-route me-2"></i>Thông tin chuyến xe</h6>
                        </div>
                        <div class="card-body">
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
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-play me-1 text-success"></i>Giờ khởi hành <span
                                                class="text-danger">*</span>
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

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-stop me-1 text-danger"></i>Giờ đến dự kiến <span
                                                class="text-danger">*</span>
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

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i>Trạng thái <span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="trang_thai" required>
                                        <option value="1" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == '1') ? 'selected' : 'selected'; ?>>
                                            Chờ khởi hành
                                        </option>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn trạng thái</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phân công tài xế -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-black">
                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Phân công tài xế</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-tie me-1"></i>Tài xế chính <span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="tai_xe_chinh" id="tai-xe-chinh" required>
                                        <option value="">-- Chọn tài xế chính --</option>
                                        <?php foreach ($tai_xe_list as $tai_xe): ?>
                                            <option value="<?php echo $tai_xe['MaTaiXe']; ?>"
                                                    <?php echo (isset($_POST['tai_xe_chinh']) && $_POST['tai_xe_chinh'] == $tai_xe['MaTaiXe']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tai_xe['MaTaiXe'] . ' - ' . $tai_xe['HoTen'] . ' (' . $tai_xe['SDT'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn tài xế chính</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-friends me-1"></i>Lái phụ<span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="lai_phu" id="lai-phu" required>
                                        <option value="">-- Không có lái phụ --</option>
                                        <?php foreach ($tai_xe_list as $tai_xe): ?>
                                            <option value="<?php echo $tai_xe['MaTaiXe']; ?>"
                                                    <?php echo (isset($_POST['lai_phu']) && $_POST['lai_phu'] == $tai_xe['MaTaiXe']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tai_xe['MaTaiXe'] . ' - ' . $tai_xe['HoTen'] . ' (' . $tai_xe['SDT'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Vui lòng chọn lái phụ</div>
                                </div>
                            </div>

                            <!-- Thông tin thù lao -->
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Thông tin thù lao</h6>
                                <div id="salary-preview">
                                    <small class="text-muted">Chọn tuyến đường để xem ước tính thù lao</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hướng dẫn -->
                    <div class="mb-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-black">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn tính thù lao</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0 small">
                                    <li><strong>Công thức:</strong> Lương = Lương_cơ_bản × Hệ_số_vai_trò × Hệ_số_khoảng_cách × Hệ_số_độ_khó</li>
                                    <li><strong>Lương cơ bản:</strong> 100,000đ</li>
                                    <li><strong>Hệ số vai trò:</strong> Tài xế chính ×1.5, Lái phụ ×1.0</li>
                                    <li><strong>Hệ số khoảng cách:</strong> (1 + độ_dài_km / 100)</li>
                                    <li><strong>Hệ số độ khó:</strong> (1 + độ_phức_tạp / 10)</li>
                                    <li><strong>Ví dụ:</strong> Tuyến 200km độ khó 2: Tài xế chính = 100k × 1.5 × 3.0 × 1.2 = 540,000đ</li>
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
    // Tự động tính giờ đến và ước tính thù lao
    document.getElementById('tuyen-select').addEventListener('change', function () {
        calculateEstimatedArrival();
        updateSalaryPreview();
    });

    document.getElementById('gio-di').addEventListener('change', function () {
        calculateEstimatedArrival();
        updateSalaryPreview();
    });

    // Ngăn chọn cùng tài xế cho 2 vai trò
    document.getElementById('tai-xe-chinh').addEventListener('change', function () {
        const laiPhuSelect = document.getElementById('lai-phu');
        const selectedValue = this.value;

        // Disable option đã chọn cho tài xế chính
        Array.from(laiPhuSelect.options).forEach(option => {
            if (option.value === selectedValue && option.value !== '') {
                option.disabled = true;
                if (laiPhuSelect.value === selectedValue) {
                    laiPhuSelect.value = '';
                }
            } else {
                option.disabled = false;
            }
        });
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
            let averageSpeed;
            switch (complexity) {
                case 1:
                    averageSpeed = 60;
                    break; // Đơn giản
                case 2:
                    averageSpeed = 50;
                    break; // Trung bình
                case 3:
                    averageSpeed = 40;
                    break; // Phức tạp
                default:
                    averageSpeed = 50;
                    break;
            }

            const travelTime = distance / averageSpeed;
            const restTime = Math.floor(distance / 100) * 0.25; // 15 phút mỗi 100km
            const totalTime = travelTime + restTime;

            const departureDate = new Date(departureTime);
            const arrivalDate = new Date(departureDate.getTime() + totalTime * 60 * 60 * 1000);

            const arrivalString = arrivalDate.toISOString().slice(0, 16);
            gioDenInput.value = arrivalString;
        }
    }

    function updateSalaryPreview() {
        const tuyenSelect = document.getElementById('tuyen-select');
        const salaryPreview = document.getElementById('salary-preview');

        const selectedOption = tuyenSelect.options[tuyenSelect.selectedIndex];
        const distance = parseFloat(selectedOption.getAttribute('data-distance'));
        const complexity = parseInt(selectedOption.getAttribute('data-complexity'));

        if (distance && complexity) {
            const luongCoBan = 100000;

            // Tính theo công thức mới
            const heSoKhoangCach = 1 + (distance / 100.0);
            const heSoDoPhucTap = 1 + (complexity / 10.0);

            // Tài xế chính (x1.5) và lái phụ (x1.0)
            const mainDriverSalary = luongCoBan * 1.5 * heSoKhoangCach * heSoDoPhucTap;
            const assistantSalary = luongCoBan * 1.0 * heSoKhoangCach * heSoDoPhucTap;

            // Tên độ phức tạp
            const complexityNames = {
                1: 'Đơn giản',
                2: 'Trung bình',
                3: 'Phức tạp'
            };

            salaryPreview.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong class="text-success">Tài xế chính:</strong> ${Math.round(mainDriverSalary).toLocaleString('vi-VN')}đ
                    <br><small class="text-muted">100,000 × 1.5 × ${heSoKhoangCach.toFixed(2)} × ${heSoDoPhucTap.toFixed(1)}</small>
                </div>
                <div class="col-md-6">
                    <strong class="text-info">Lái phụ:</strong> ${Math.round(assistantSalary).toLocaleString('vi-VN')}đ
                    <br><small class="text-muted">100,000 × 1.0 × ${heSoKhoangCach.toFixed(2)} × ${heSoDoPhucTap.toFixed(1)}</small>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Chi tiết:</strong>
                        Lương CB: 100,000đ |
                        Khoảng cách: ${distance}km (×${heSoKhoangCach.toFixed(2)}) |
                        Độ khó: ${complexityNames[complexity] || 'N/A'} (×${heSoDoPhucTap.toFixed(1)})
                    </small>
                </div>
            </div>
        `;
        } else {
            salaryPreview.innerHTML = '<small class="text-muted">Chọn tuyến đường để xem ước tính thù lao</small>';
        }
    }

    // Validation giờ đến
    document.getElementById('gio-di').addEventListener('change', function () {
        document.getElementById('gio-den').min = this.value;
    });
</script>

<?php include '../includes/footer.php'; ?>
