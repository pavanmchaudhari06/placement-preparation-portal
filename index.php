<?php
/**
 * Placement Preparation Portal - Student Landing Page: index.php
 */
require_once 'includes/student_auth.php';

// If student is already logged in, redirect straight to their dashboard
if (is_student_logged_in()) {
    header("Location: student/dashboard.php");
    exit;
}

$page_title = "Welcome - Placement Preparation Portal";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/student.css">
</head>
<body>

<!-- Public Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-mortarboard-fill fs-3"></i>
            <span>Placement Portal</span>
        </a>
        <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-in-right me-1"></i> Student Login
            </a>
            <a href="register.php" class="btn btn-light text-primary fw-semibold">
                <i class="bi bi-person-plus-fill me-1"></i> Register
            </a>
        </div>
    </div>
</nav>

<!-- Hero Banner -->
<header class="py-5 bg-white border-bottom">
    <div class="container text-center py-4">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3 fw-semibold">
            🚀 All-In-One Campus Placement Preparation
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">
            Master Your Campus Placements in One Place
        </h1>
        <p class="lead text-secondary mx-auto col-lg-8 mb-4">
            No more switching between multiple websites. Practice Aptitude tests, solve coding problems, prepare for HR and Technical interviews, view company-specific patterns, build an ATS-friendly resume, and track your weak areas automatically.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="register.php" class="btn btn-primary btn-lg px-4 shadow-sm">
                <i class="bi bi-mortarboard-fill me-2"></i> Join as Student
            </a>
            <a href="login.php" class="btn btn-outline-primary btn-lg px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i> Student Login
            </a>
        </div>
    </div>
</header>

<!-- Key Highlights Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Everything You Need for Placement Success</h2>
            <p class="text-muted">A structured learning pathway designed specifically for college students.</p>
        </div>

        <div class="row g-4">
            <!-- 1. Aptitude Tests -->
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="stat-icon blue mb-3">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Topic-wise Aptitude Tests</h5>
                    <p class="text-muted small">
                        Timed MCQ assessments covering Quantitative, Logical Reasoning, and Verbal Ability with detailed step-by-step explanations for each question.
                    </p>
                </div>
            </div>

            <!-- 2. Coding Practice -->
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="stat-icon green mb-3">
                        <i class="bi bi-code-slash"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Curated Coding Problems</h5>
                    <p class="text-muted small">
                        Practice Easy, Medium, and Hard coding questions frequently asked in technical rounds. Integrated workspace with sample test cases.
                    </p>
                </div>
            </div>

            <!-- 3. Smart Recommendations -->
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="stat-icon red mb-3">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Weakness Recommendations</h5>
                    <p class="text-muted small">
                        The portal tracks your test scores and alerts you when your performance falls below 50% in any topic so you know exactly what to revise.
                    </p>
                </div>
            </div>

            <!-- 4. Interview Preparation -->
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="stat-icon purple mb-3">
                        <i class="bi bi-chat-quote-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark">HR & Technical Q&A</h5>
                    <p class="text-muted small">
                        Comprehensive answers and answering strategies for foundational OOPs, DBMS, Operating Systems, Networking, and common HR interview questions.
                    </p>
                </div>
            </div>

            <!-- 5. Company-wise Questions -->
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="stat-icon yellow mb-3">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Company Patterns</h5>
                    <p class="text-muted small">
                        Targeted questions asked by top IT recruiters including TCS, Infosys, Wipro, Capgemini, and Accenture with round-by-round insights.
                    </p>
                </div>
            </div>

            <!-- 6. ATS Resume Builder -->
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="stat-icon blue mb-3">
                        <i class="bi bi-file-earmark-person-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark">ATS-Friendly Resume Builder</h5>
                    <p class="text-muted small">
                        Generate clean, professional, single-column resumes that pass recruitment software filters and can be printed or saved as PDF with one click.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-white border-top py-4 text-center text-muted">
    <div class="container">
        <p class="mb-1"><strong>Placement Preparation Portal</strong> &copy; <?php echo date('Y'); ?>. Built with PHP, MySQL & Bootstrap 5.</p>
        <p class="small mb-0 text-secondary">A College Placement Readiness Platform for Students.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
