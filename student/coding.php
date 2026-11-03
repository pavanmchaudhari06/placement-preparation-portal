<?php
/**
 * Student Coding Practice Module: coding.php
 * Displays coding problems with difficulty filters and solved status indicators.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$user = get_logged_user();
$student_id = $user['id'];

// Filter by Difficulty if set
$difficulty_filter = trim($_GET['difficulty'] ?? 'All');
$allowed_diff = ['All', 'Easy', 'Medium', 'Hard'];

if (!in_array($difficulty_filter, $allowed_diff)) {
    $difficulty_filter = 'All';
}

$where_clause = "";
if ($difficulty_filter !== 'All') {
    $safe_diff = mysqli_real_escape_string($conn, $difficulty_filter);
    $where_clause = "WHERE cp.difficulty = '$safe_diff'";
}

// Fetch problems along with solved indicator for this student
$query = "SELECT cp.*, 
                 IF(sp.id IS NOT NULL, 1, 0) AS is_solved
          FROM coding_problems cp
          LEFT JOIN solved_problems sp ON cp.id = sp.problem_id AND sp.user_id = $student_id
          $where_clause
          ORDER BY FIELD(cp.difficulty, 'Easy', 'Medium', 'Hard'), cp.id ASC";
$result = mysqli_query($conn, $query);

// Statistics
$total_solved_res = mysqli_query($conn, "SELECT COUNT(*) as solved FROM solved_problems WHERE user_id = $student_id");
$total_solved = mysqli_fetch_assoc($total_solved_res)['solved'] ?? 0;

$total_prob_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM coding_problems");
$total_prob = mysqli_fetch_assoc($total_prob_res)['total'] ?? 0;

$page_title = "Coding Practice";
$page_heading = "Coding Practice";
$current_page = "coding";

require_once '../includes/student_header.php';
?>

<!-- Header Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card content-card border-0 bg-dark text-white p-4 h-100">
            <h4 class="fw-bold mb-1"><i class="bi bi-code-slash text-success me-2"></i> Data Structures & Algorithms Practice</h4>
            <p class="text-white-50 mb-0">
                Solve essential coding questions asked in technical rounds. Read problem descriptions, test with sample cases in your code editor, and mark problems as solved.
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card h-100 d-flex flex-column justify-content-center text-center">
            <div class="fs-6 text-muted mb-1">Your Coding Progress</div>
            <div class="display-6 fw-bold text-primary"><?php echo $total_solved; ?> / <?php echo $total_prob; ?></div>
            <div class="small text-muted">Problems Completed</div>
        </div>
    </div>
</div>

<!-- Difficulty Filter Buttons -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="btn-group shadow-sm" role="group">
        <a href="coding.php?difficulty=All" class="btn <?php echo ($difficulty_filter === 'All') ? 'btn-primary' : 'btn-outline-primary'; ?>">
            All Problems
        </a>
        <a href="coding.php?difficulty=Easy" class="btn <?php echo ($difficulty_filter === 'Easy') ? 'btn-success' : 'btn-outline-success'; ?>">
            Easy
        </a>
        <a href="coding.php?difficulty=Medium" class="btn <?php echo ($difficulty_filter === 'Medium') ? 'btn-warning' : 'btn-outline-warning text-dark'; ?>">
            Medium
        </a>
        <a href="coding.php?difficulty=Hard" class="btn <?php echo ($difficulty_filter === 'Hard') ? 'btn-danger' : 'btn-outline-danger'; ?>">
            Hard
        </a>
    </div>

    <span class="text-muted small">
        Showing <strong><?php echo mysqli_num_rows($result); ?></strong> problems
    </span>
</div>

<!-- Problems Table -->
<div class="content-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Status</th>
                    <th>Problem Title</th>
                    <th>Difficulty</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($prob = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <?php if ($prob['is_solved']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle-fill me-1"></i> Solved
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        Unsolved
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="coding_solve.php?id=<?php echo $prob['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($prob['title']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($prob['difficulty'] === 'Easy'): ?>
                                    <span class="badge bg-success">Easy</span>
                                <?php elseif ($prob['difficulty'] === 'Medium'): ?>
                                    <span class="badge bg-warning text-dark">Medium</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Hard</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="coding_solve.php?id=<?php echo $prob['id']; ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                                    Solve Problem <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            No coding problems found under this filter.
                        </td>
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
