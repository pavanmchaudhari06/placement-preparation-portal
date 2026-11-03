<?php
/**
 * Admin Header & Navigation Template: admin_header.php
 * Enterprise Dark Slate & Emerald Green theme, administrator navigation only.
 */
if (!isset($page_title)) {
    $page_title = "Admin Console";
}

// Automatically resolve relative path
$asset_path = file_exists('css/admin.css') ? '' : '../';
$admin = get_admin_user();
$active = $current_page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Placement Portal Administration</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Admin Custom Theme CSS (Dark Grey & Emerald Green) -->
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/admin.css">
</head>
<body>

<div class="admin-dashboard-wrapper">
    <!-- Admin Left Navigation Sidebar -->
    <nav class="admin-sidebar">
        <div class="admin-brand">
            <i class="bi bi-shield-lock-fill fs-4"></i>
            <span>Portal Admin</span>
        </div>
        
        <ul class="nav-links">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($active === 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="aptitude_manage.php" class="nav-link <?php echo ($active === 'aptitude') ? 'active' : ''; ?>">
                    <i class="bi bi-patch-question"></i>
                    <span>Manage Aptitude</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="coding_manage.php" class="nav-link <?php echo ($active === 'coding') ? 'active' : ''; ?>">
                    <i class="bi bi-code-square"></i>
                    <span>Manage Coding</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="interview_manage.php" class="nav-link <?php echo ($active === 'interview') ? 'active' : ''; ?>">
                    <i class="bi bi-chat-left-text"></i>
                    <span>Manage Interview Qs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="company_manage.php" class="nav-link <?php echo ($active === 'company') ? 'active' : ''; ?>">
                    <i class="bi bi-building"></i>
                    <span>Manage Company Qs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="students_report.php" class="nav-link <?php echo ($active === 'reports') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Students & Reports</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Admin Logout</span>
                </a>
            </li>
        </ul>

        <div class="user-info">
            <div class="fw-bold text-white"><?php echo htmlspecialchars($admin['name'] ?? 'Administrator'); ?></div>
            <div class="text-secondary small"><?php echo htmlspecialchars($admin['email'] ?? ''); ?></div>
        </div>
    </nav>

    <!-- Admin Main Content Area -->
    <div class="admin-main-content">
        <!-- Admin Top Navbar -->
        <header class="admin-top-navbar">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-5 fw-bold text-dark"><?php echo htmlspecialchars($page_heading ?? 'Admin Console'); ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success px-3 py-2 rounded-pill">
                    <i class="bi bi-shield-check me-1"></i> Admin Console
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </header>

        <!-- Content Body Wrapper -->
        <main class="admin-content-body">
