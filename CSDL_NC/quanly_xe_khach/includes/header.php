<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Quản lý xe khách'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : '../style.css'; ?>">

    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .btn-group-sm > .btn, .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .form-floating > label {
            color: #6c757d;
        }
        .alert {
            border: none;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo isset($nav_path) ? $nav_path : '../index.php'; ?>">
            <i class="fas fa-bus me-2"></i>Quản lý xe khách
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-1"></i>Quản lý
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>loai_xe/index.php">
                                <i class="fas fa-car me-2"></i>Loại xe</a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>xe_khach/index.php">
                                <i class="fas fa-bus me-2"></i>Xe khách</a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>tuyen_duong/index.php">
                                <i class="fas fa-route me-2"></i>Tuyến đường</a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>tai_xe/index.php">
                                <i class="fas fa-user-tie me-2"></i>Tài xế</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>bang_gia/index.php">
                                <i class="fas fa-tags me-2"></i>Bảng giá</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar-alt me-1"></i>Hoạt động
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>chuyen_xe/index.php">
                                <i class="fas fa-road me-2"></i>Chuyến xe</a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>ve_xe/index.php">
                                <i class="fas fa-ticket-alt me-2"></i>Vé xe</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar"></i> Báo cáo
                    </a>
                    <ul class="dropdown-menu">
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>/reports/salary_report.php">
                                <i class="fas fa-money-bill-wave me-2"></i>Lương tháng tài xế
                            </a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>/reports/vehicle_revenue_report.php">
                                <i class="fas fa-bus me-2"></i>Doanh thu theo xe
                            </a></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>/reports/route_revenue_report.php">
                                <i class="fas fa-route me-2"></i>Doanh thu theo tuyến
                            </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>/reports/maintenance_report.php">
                                <i class="fas fa-tools me-2"></i>Tình trạng bảo dưỡng
                            </a></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo isset($nav_path) ? $nav_path : '../'; ?>/reports/overdue_maintenance.php">
                                <i class="fas fa-exclamation-triangle me-2"></i>Xe quá hạn BD
                            </a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item">
                        <span class="navbar-text">
                            <i class="fas fa-clock me-1"></i>
                            <span id="current-time"></span>
                        </span>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container-fluid mt-4">
