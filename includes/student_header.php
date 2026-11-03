<?php
/**
 * Student Header & Navigation Template: student_header.php
 * Blue theme, student navigation only.
 */
if (!isset($page_title)) {
    $page_title = "Student Portal";
}

// Automatically resolve relative path
$asset_path = file_exists('css/student.css') ? '' : '../';
$student = get_student_user();
$active = $current_page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Placement Preparation Portal</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Student Custom Theme CSS (Academic Blue) -->
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/student.css">

    <!-- Chart.js for Progress Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Student Sidebar -->
    <nav class="sidebar">
        <div class="brand">
            <i class="bi bi-mortarboard-fill text-primary fs-4"></i>
            <span>Placement Portal</span>
        </div>
        
        <ul class="nav-links">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($active === 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="aptitude.php" class="nav-link <?php echo ($active === 'aptitude') ? 'active' : ''; ?>">
                    <i class="bi bi-journal-check"></i>
                    <span>Aptitude Test</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="coding.php" class="nav-link <?php echo ($active === 'coding') ? 'active' : ''; ?>">
                    <i class="bi bi-code-slash"></i>
                    <span>Coding Practice</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="interview.php" class="nav-link <?php echo ($active === 'interview') ? 'active' : ''; ?>">
                    <i class="bi bi-chat-square-dots"></i>
                    <span>Interview Prep</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="company.php" class="nav-link <?php echo ($active === 'company') ? 'active' : ''; ?>">
                    <i class="bi bi-buildings"></i>
                    <span>Company-wise Qs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="resume.php" class="nav-link <?php echo ($active === 'resume') ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-person"></i>
                    <span>Resume Builder</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>

        <div class="user-info">
            <div class="fw-bold text-white"><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></div>
            <div class="text-secondary small"><?php echo htmlspecialchars($student['email'] ?? ''); ?></div>
        </div>
    </nav>

    <!-- Student Main Content Area -->
    <div class="main-content">
        <!-- Student Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-5 fw-bold text-dark"><?php echo htmlspecialchars($page_heading ?? 'Student Portal'); ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary px-3 py-2 rounded-pill">
                    <i class="bi bi-person-fill me-1"></i> Student Mode
                </span>
                <a href="../logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </header>

        <!-- Content Body Wrapper -->
        <main class="content-body">
