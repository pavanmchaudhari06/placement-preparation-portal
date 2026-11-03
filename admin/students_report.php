<?php
/**
 * Administrator Student Performance Report: students_report.php
 * Displays comprehensive performance report for each student, printable for college faculty review.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

require_admin();

$search = trim($_GET['search'] ?? '');
$search_sql = "";
if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $search_sql = "AND (u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%')";
}

// Fetch students with their aggregated test count, average percentage, and solved coding problem count
$query = "SELECT u.id, u.name, u.email, u.created_at,
                 COUNT(DISTINCT tr.id) AS tests_taken,
                 ROUND(AVG(tr.percentage), 1) AS avg_score,
                 COUNT(DISTINCT sp.id) AS solved_problems
          FROM users u
          LEFT JOIN test_results tr ON u.id = tr.user_id
          LEFT JOIN solved_problems sp ON u.id = sp.user_id
          WHERE 1=1 $search_sql
          GROUP BY u.id
          ORDER BY u.id DESC";
$result = mysqli_query($conn, $query);

// Summary statistics
$total_students_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM users");
$total_students = mysqli_fetch_assoc($total_students_res)['c'] ?? 0;

$overall_avg_res = mysqli_query($conn, "SELECT ROUND(AVG(percentage), 1) as avg_p FROM test_results");
$overall_avg = mysqli_fetch_assoc($overall_avg_res)['avg_p'] ?? 0;

$page_title = "Students Performance Report";
$page_heading = "Student Performance Report";
$current_page = "reports";

require_once '../includes/admin_header.php';
?>

<!-- Action Bar & Report Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 bg-white p-3 rounded border shadow-sm">
    <div>
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i> Student Placement Readiness Report
        </h5>
        <span class="text-muted small">Generated on <?php echo date('d F Y'); ?> | Total Enrolled: <?php echo $total_students; ?> | Average Score: <?php echo $overall_avg; ?>%</span>
    </div>
    <div class="d-flex gap-2 no-print">
        <button onclick="window.print()" class="btn btn-primary fw-semibold">
            <i class="bi bi-printer-fill me-1"></i> Print / Save Report as PDF
        </button>
    </div>
</div>

<!-- Search Bar (Hidden during printing) -->
<div class="no-print row mb-4">
    <div class="col-md-6 col-lg-4">
        <form method="GET" action="students_report.php" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search student name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-search"></i>
            </button>
            <?php if (!empty($search)): ?>
                <a href="students_report.php" class="btn btn-outline-danger" title="Clear Search">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="content-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Student Name</th>
                    <th>Email Address</th>
                    <th>Registered On</th>
                    <th class="text-center">Tests Taken</th>
                    <th class="text-center">Average Score</th>
                    <th class="text-center">Coding Solved</th>
                    <th>Readiness Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $i = 0; while ($row = mysqli_fetch_assoc($result)): $i++; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="small text-muted"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary fs-6 px-3">
                                    <?php echo $row['tests_taken']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($row['tests_taken'] > 0): ?>
                                    <div class="fw-bold fs-6 <?php echo ($row['avg_score'] >= 70) ? 'text-success' : (($row['avg_score'] >= 50) ? 'text-primary' : 'text-danger'); ?>">
                                        <?php echo $row['avg_score']; ?>%
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">No tests</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success fs-6 px-3">
                                    <?php echo $row['solved_problems']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['tests_taken'] == 0): ?>
                                    <span class="badge bg-secondary">Unattempted</span>
                                <?php elseif ($row['avg_score'] >= 75): ?>
                                    <span class="badge bg-success">Placement Ready</span>
                                <?php elseif ($row['avg_score'] >= 50): ?>
                                    <span class="badge bg-primary">Average (Progressing)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Needs Mentoring (<50%)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No student records match your query.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Print Styles for Report -->
<style>
@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    .no-print, .sidebar, .top-navbar, .btn {
        display: none !important;
    }
    .dashboard-wrapper {
        display: block !important;
    }
    .main-content, .content-body {
        padding: 0 !important;
        margin: 0 !important;
    }
    .content-card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
