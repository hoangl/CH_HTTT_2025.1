<?php
$page_title = "Chỉnh sửa phân công";
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Lấy thông tin từ URL
$ma_chuyen_xe = isset($_GET['chuyen']) ? $_GET['chuyen'] : '';
$ma_tai_xe = isset($_GET['tai_xe']) ? $_GET['tai_xe'] : '';

if (empty($ma_chuyen_xe) || empty($ma_tai_xe)) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin phân công hiện tại
try {
    $query = "SELECT pc.*, 
                     cx.GioDi, cx.GioDen, cx.TrangThai,
                     td.DiemDau, td.DiemCuoi, td.DoDai,
                     xk.MaXe, lx.TenLoaiXe,
                     tx.HoTen, tx.SDT
              FROM phan_cong pc
              LEFT JOIN chuyen_xe cx ON pc.MaChuyenXe = cx.MaChuyenXe
              LEFT JOIN tuyen_duong td ON cx.MaTuyenDuong = td.MaTuyenDuong
              LEFT JOIN xe_khach xk ON cx.MaXe = xk.MaXe
              LEFT JOIN loai_xe lx ON xk.MaLoaiXe = lx.MaLoaiXe
              LEFT JOIN tai_xe tx ON pc.MaTaiXe = tx.MaTaiXe
              WHERE pc.MaChuyenXe = ? AND pc.MaTaiXe = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$ma_chuyen_xe, $ma_tai_xe]);
    $phan_cong = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phan_cong) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

// Xử lý cập nhật
if ($_POST && !$error) {
    $vai_tro = intval($_POST['vai_tro']);
    $thu_lao = floatval($_POST['thu_lao']);

    // Validation
    if ($vai_tro < 1 || $vai_tro > 2 || $thu_lao <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin hợp lệ!";
    } elseif ($thu_lao > 10000000) {
        $error = "Thu lao không được vượt quá 10,000,000đ!";
    } else {
        try {
            // Kiểm tra nếu thay đổi vai trò thành tài xế chính
            if ($vai_tro == 1 && $phan_cong['VaiTro'] != 1) {
                $check_main_query = "SELECT COUNT(*) FROM phan_cong WHERE MaChuyenXe = ? AND VaiTro = 1";
                $check_main_stmt = $db->prepare($check_main_query);
                $check_main_stmt->execute([$ma_chuyen_xe]);

                if ($check_main_stmt->fetchColumn() > 0) {
                    $error = "Chuyến xe này đã có tài xế chính!";
                }
            }

            if (!$error) {
                $query = "UPDATE phan_cong SET VaiTro = ?, ThuLao = ? WHERE MaChuyenXe = ? AND MaTaiXe = ?";
                $stmt = $db->prepare($query);

                if ($stmt->execute([$vai_tro, $thu_lao, $ma_chuyen_xe, $ma_tai_xe])) {
                    $success = "Cập nhật phân công thành công!";
                    $phan_cong['VaiTro'] = $vai_tro;
                    $phan_cong['ThuLao'] = $thu_lao;
                } else {
                    $error = "Có lỗi xảy ra khi cập nhật phân công!";
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
                    Chỉnh sửa phân công
                </h4>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Phân công</a></li>
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

                <?php if ($phan_cong): ?>
                    <!-- Thông tin cố định -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle text-info me-2"></i>Thông tin cố định
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Chuyến xe:</strong><br>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($phan_cong['MaChuyenXe']); ?></span><br><br>
                                        <strong>Tuyến đường:</strong><br>
                                        <span class="fw-bold"><?php echo htmlspecialchars($phan_cong['DiemDau']); ?></span>
                                        <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                        <span class="fw-bold"><?php echo htmlspecialchars($phan_cong['DiemCuoi']); ?></span><br>
                                        <small class="text-muted"><?php echo number_format($phan_cong['DoDai'], 1); ?> km</small>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Tài xế:</strong><br>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($phan_cong['MaTaiXe']); ?></span><br>
                                        <span class="fw-bold"><?php echo htmlspecialchars($phan_cong['HoTen']); ?></span><br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($phan_cong['SDT']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <strong>Thời gian:</strong><br>
                                        <small>
                                            <strong>Đi:</strong> <?php echo date('d/m/Y H:i', strtotime($phan_cong['GioDi'])); ?><br>
                                            <strong>Đến:</strong> <?php echo date('d/m/Y H:i', strtotime($phan_cong['GioDen'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Xe:</strong><br>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($phan_cong['MaXe']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($phan_cong['TenLoaiXe']); ?></small>
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
                                    <i class="fas fa-user-cog me-1"></i>Vai trò <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="vai_tro" id="vai-tro-select" required>
                                    <option value="1" <?php echo (isset($_POST['vai_tro']) ?
                                        ($_POST['vai_tro'] == 1 ? 'selected' : '') :
                                        ($phan_cong['VaiTro'] == 1 ? 'selected' : '')); ?>>
                                        Tài xế chính
                                    </option>
                                    <option value="2" <?php echo (isset($_POST['vai_tro']) ?
                                        ($_POST['vai_tro'] == 2 ? 'selected' : '') :
                                        ($phan_cong['VaiTro'] == 2 ? 'selected' : '')); ?>>
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
                                           value="<?php echo isset($_POST['thu_lao']) ? $_POST['thu_lao'] : $phan_cong['ThuLao']; ?>"
                                           min="50000"
                                           max="10000000"
                                           step="10000"
                                           required>
                                    <span class="input-group-text">đ</span>
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập thu lao hợp lệ</div>
                            </div>
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
                                            <strong>Hiện tại:</strong><br>
                                            <span class="text-muted">
                                            Vai trò: <?php echo $phan_cong['VaiTro'] == 1 ? 'Tài xế chính' : 'Lái phụ'; ?><br>
                                            Thu lao: <?php echo number_format($phan_cong['ThuLao'], 0, ',', '.'); ?>đ
                                        </span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Sẽ thay đổi thành:</strong><br>
                                            <span id="change-info" class="text-success">
                                            Sẽ cập nhật khi thay đổi
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gợi ý thu lao -->
                        <div class="mb-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Gợi ý thu lao</h6>
                                </div>
                                <div class="card-body">
                                    <div id="salary-suggestion">
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
                            <a href="view.php?chuyen=<?php echo $phan_cong['MaChuyenXe']; ?>&tai_xe=<?php echo $phan_cong['MaTaiXe']; ?>" class="btn btn-info">
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
    const distance = <?php echo $phan_cong['DoDai']; ?>;
    const departure = new Date('<?php echo $phan_cong['GioDi']; ?>');
    const originalRole = <?php echo $phan_cong['VaiTro']; ?>;
    const originalSalary = <?php echo $phan_cong['ThuLao']; ?>;

    // Cập nhật thông tin khi thay đổi
    document.getElementById('vai-tro-select').addEventListener('change', updateChangeInfo);
    document.getElementById('thu-lao').addEventListener('input', updateChangeInfo);

    function updateChangeInfo() {
        const newRole = document.getElementById('vai-tro-select').value;
        const newSalary = parseFloat(document.getElementById('thu-lao').value);
        const changeInfo = document.getElementById('change-info');

        if (newRole && newSalary) {
            const roleText = newRole == '1' ? 'Tài xế chính' : 'Lái phụ';
            const salaryChange = newSalary - originalSalary;
            const roleChange = newRole != originalRole;

            let changeText = `Vai trò: ${roleText}<br>Thu lao: ${newSalary.toLocaleString('vi-VN')}đ`;

            if (salaryChange !== 0) {
                const changePercent = ((salaryChange / originalSalary) * 100).toFixed(1);
                const changeClass = salaryChange > 0 ? 'text-success' : 'text-danger';
                changeText += `<br><span class="${changeClass}">
                (${salaryChange > 0 ? '+' : ''}${salaryChange.toLocaleString('vi-VN')}đ, ${changePercent > 0 ? '+' : ''}${changePercent}%)
            </span>`;
            }

            if (roleChange) {
                changeText += '<br><span class="badge bg-warning text-dark">Thay đổi vai trò</span>';
            }

            changeInfo.innerHTML = changeText;

            // Cập nhật gợi ý
            updateSalarySuggestion(newRole);
        }
    }

    function updateSalarySuggestion(role) {
        const isNightTrip = departure.getHours() < 6 || departure.getHours() >= 22;
        const salarySuggestion = document.getElementById('salary-suggestion');

        // Tính thu lao gợi ý
        let baseRate = role == '1' ? 200 : 140; // đ/km
        let suggestedSalary = distance * baseRate;

        if (isNightTrip) {
            suggestedSalary *= 1.3;
        }

        if (distance > 500) {
            suggestedSalary *= 1.2;
        }

        suggestedSalary = Math.round(suggestedSalary / 10000) * 10000;

        const minSalary = Math.round(suggestedSalary * 0.8);
        const maxSalary = Math.round(suggestedSalary * 1.2);

        salarySuggestion.innerHTML = `
        <strong>Gợi ý cho ${role == '1' ? 'Tài xế chính' : 'Lái phụ'}:</strong>
        <span class="text-success fw-bold">${suggestedSalary.toLocaleString('vi-VN')}đ</span>
        <small class="text-muted">(${minSalary.toLocaleString('vi-VN')}đ - ${maxSalary.toLocaleString('vi-VN')}đ)</small>
        ${isNightTrip ? '<span class="badge bg-warning text-dark ms-2">Chuyến đêm</span>' : ''}
        ${distance > 500 ? '<span class="badge bg-info ms-1">Chuyến dài</span>' : ''}
    `;
    }

    // Khởi tạo
    document.addEventListener('DOMContentLoaded', function() {
        updateChangeInfo();
    });
</script>

<?php include '../includes/footer.php'; ?>
