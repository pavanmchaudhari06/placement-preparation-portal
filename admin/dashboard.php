<?php
/**
 * Administrator Dashboard: dashboard.php
 * Displays portal-wide stats, student activities, and management shortcuts.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

require_admin();

// 1. Total Registered Students
$stud_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_students = mysqli_fetch_assoc($stud_res)['total'] ?? 0;

// 2. Total Aptitude Questions
$apt_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM aptitude_questions");
$total_aptitude = mysqli_fetch_assoc($apt_res)['total'] ?? 0;

// 3. Total Coding Problems
$code_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM coding_problems");
$total_coding = mysqli_fetch_assoc($code_res)['total'] ?? 0;

// 4. Total Interview & Company Questions
$int_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM interview_questions");
$total_interview = mysqli_fetch_assoc($int_res)['total'] ?? 0;

$comp_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM company_questions");
$total_company = mysqli_fetch_assoc($comp_res)['total'] ?? 0;

// 5. Total Tests Attempted
$tests_res = mysqli_query($conn, "SELECT COUNT(*) as total, AVG(percentage) as avg_p FROM test_results");
$tests_data = mysqli_fetch_assoc($tests_res);
$total_tests = $tests_data['total'] ?? 0;
$avg_portal_score = round($tests_data['avg_p'] ?? 0, 1);

// Recent 6 Test Submissions across all students
$recent_query = "SELECT tr.*, u.name as student_name, u.email as student_email 
                 FROM test_results tr
                 JOIN users u ON tr.user_id = u.id
                 ORDER BY tr.taken_at DESC LIMIT 6";
$recent_res = mysqli_query($conn, $recent_query);

$page_title = "Admin Dashboard";
$page_heading = "Administrator Dashboard";
$current_page = "dashboard";

require_once '../includes/admin_header.php';
?>

<!-- Statistics Overview Cards -->
<div class="row g-4 mb-4">
    <!-- Total Students -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $total_students; ?></div>
                <div class="text-muted small">Registered Students</div>
            </div>
        </div>
    </div>

    <!-- Total Tests Taken -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon green">
                <i class="bi bi-journal-check"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $total_tests; ?></div>
                <div class="text-muted small">Tests Conducted (Avg <?php echo $avg_portal_score; ?>%)</div>
            </div>
        </div>
    </div>

    <!-- Aptitude Questions -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon purple">
                <i class="bi bi-patch-question"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $total_aptitude; ?></div>
                <div class="text-muted small">Aptitude Questions</div>
            </div>
        </div>
    </div>

    <!-- Coding Problems -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon yellow">
                <i class="bi bi-code-square"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $total_coding; ?></div>
                <div class="text-muted small">Coding Challenges</div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Quick Action Management Buttons -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="aptitude_manage.php" class="btn btn-outline-primary w-100 p-3 text-start shadow-sm bg-white">
            <i class="bi bi-plus-circle-fill text-primary fs-5 mb-1 d-block"></i>
            <strong>Manage Aptitude Qs</strong>
            <div class="small text-muted">Add / Edit Quant, Logic, Verbal</div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="coding_manage.php" class="btn btn-outline-success w-100 p-3 text-start shadow-sm bg-white">
            <i class="bi bi-plus-circle-fill text-success fs-5 mb-1 d-block"></i>
            <strong>Manage Coding Qs</strong>
            <div class="small text-muted">Add / Edit DSA problems</div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="company_manage.php" class="btn btn-outline-warning w-100 p-3 text-start shadow-sm bg-white">
            <i class="bi bi-plus-circle-fill text-warning fs-5 mb-1 d-block"></i>
            <strong>Manage Company Qs</strong>
            <div class="small text-muted">TCS, Infosys, Wipro, Capgemini</div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="students_report.php" class="btn btn-outline-info w-100 p-3 text-start shadow-sm bg-white">
            <i class="bi bi-file-earmark-bar-graph-fill text-info fs-5 mb-1 d-block"></i>
            <strong>Generate Student Report</strong>
            <div class="small text-muted">View scores & test activity</div>
        </a>
    </div>
</div>

<!-- Recent Student Activity Feed -->
<div class="content-card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold">Live Student Test Submissions</span>
        <a href="students_report.php" class="btn btn-sm btn-outline-primary">View Full Report</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Topic</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent_res) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($recent_res)): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($row['student_email']); ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($row['topic']); ?></span></td>
                            <td><strong><?php echo $row['score']; ?></strong> / <?php echo $row['total_questions']; ?></td>
                            <td>
                                <span class="fw-bold <?php echo ($row['percentage'] >= 50) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $row['percentage']; ?>%
                                </span>
                            </td>
                            <td>
                                <?php if ($row['percentage'] >= 50): ?>
                                    <span class="badge bg-success">Passed</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Needs Practice</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?php echo date('d M Y, h:i A', strtotime($row['taken_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No test submissions recorded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
