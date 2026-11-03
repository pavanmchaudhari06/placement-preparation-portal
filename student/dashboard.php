<?php
/**
 * Student Dashboard: dashboard.php
 * Displays test statistics, Chart.js progress chart, weak topic recommendations, and recent activity.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

// Enforce student role
require_student();

$user = get_logged_user();
$student_id = $user['id'];

// 1. Fetch Total Tests Taken & Overall Average Score
$test_stats_query = "SELECT COUNT(*) AS total_tests, AVG(percentage) AS avg_score 
                     FROM test_results 
                     WHERE user_id = $student_id";
$test_stats_res = mysqli_query($conn, $test_stats_query);
$test_stats = mysqli_fetch_assoc($test_stats_res);
$total_tests = $test_stats['total_tests'] ?? 0;
$avg_score = round($test_stats['avg_score'] ?? 0, 1);

// 2. Fetch Total Solved Coding Problems
$solved_query = "SELECT COUNT(*) AS total_solved FROM solved_problems WHERE user_id = $student_id";
$solved_res = mysqli_query($conn, $solved_query);
$solved_data = mysqli_fetch_assoc($solved_res);
$total_solved = $solved_data['total_solved'] ?? 0;

// 3. Fetch Total Available Coding Problems
$total_problems_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM coding_problems");
$total_problems = mysqli_fetch_assoc($total_problems_res)['total'] ?? 0;

// 4. Topic-wise Performance for Recommendations & Chart.js
$topics = ['Quantitative', 'Logical', 'Verbal'];
$topic_scores = [];
$recommendations = [];

foreach ($topics as $topic) {
    $t_query = "SELECT AVG(percentage) AS topic_avg, COUNT(*) AS attempts 
                FROM test_results 
                WHERE user_id = $student_id AND topic = '$topic'";
    $t_res = mysqli_query($conn, $t_query);
    $t_row = mysqli_fetch_assoc($t_res);

    $t_attempts = $t_row['attempts'] ?? 0;
    $t_avg = ($t_attempts > 0) ? round($t_row['topic_avg'], 1) : null;

    $topic_scores[$topic] = [
        'avg' => $t_avg ?? 0,
        'attempts' => $t_attempts
    ];

    // Weak Topic Recommendation Logic (< 50% or unattempted)
    if ($t_attempts === 0) {
        $recommendations[] = [
            'topic' => $topic,
            'type' => 'unattempted',
            'message' => "You have not attempted any {$topic} test yet. Taking a diagnostic test helps identify your baseline."
        ];
    } elseif ($t_avg < 50) {
        $recommendations[] = [
            'topic' => $topic,
            'type' => 'weak',
            'score' => $t_avg,
            'message' => "Your average score in {$topic} is {$t_avg}% (below 50%). Practice this topic to boost your overall placement cutoff!"
        ];
    }
}

// 5. Fetch Recent 5 Test Attempts
$recent_tests_query = "SELECT * FROM test_results 
                       WHERE user_id = $student_id 
                       ORDER BY taken_at DESC LIMIT 5";
$recent_tests_res = mysqli_query($conn, $recent_tests_query);

// 6. Fetch Recent Solved Coding Problems
$recent_solved_query = "SELECT cp.title, cp.difficulty, sp.solved_at 
                        FROM solved_problems sp
                        JOIN coding_problems cp ON sp.problem_id = cp.id
                        WHERE sp.user_id = $student_id
                        ORDER BY sp.solved_at DESC LIMIT 5";
$recent_solved_res = mysqli_query($conn, $recent_solved_query);

$page_title = "Student Dashboard";
$page_heading = "Student Dashboard";
$current_page = "dashboard";

require_once '../includes/student_header.php';
?>

<div class="row g-4 mb-4">
    <!-- Stat 1: Total Tests Taken -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon blue">
                <i class="bi bi-journal-text"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $total_tests; ?></div>
                <div class="text-muted small">Tests Completed</div>
            </div>
        </div>
    </div>

    <!-- Stat 2: Average Test Score -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon green">
                <i class="bi bi-percent"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $avg_score; ?>%</div>
                <div class="text-muted small">Average Score</div>
            </div>
        </div>
    </div>

    <!-- Stat 3: Coding Problems Solved -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon purple">
                <i class="bi bi-code-slash"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark"><?php echo $total_solved; ?> / <?php echo $total_problems; ?></div>
                <div class="text-muted small">Coding Problems Solved</div>
            </div>
        </div>
    </div>

    <!-- Stat 4: Topics Covered -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon yellow">
                <i class="bi bi-award-fill"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark">3 Topics</div>
                <div class="text-muted small">Quant, Logic, Verbal</div>
            </div>
        </div>
    </div>
</div>

<!-- =======================================================
     "Recommended for You" - Weakness & Improvement Section
     ======================================================= -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card border-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f0f7ff);">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-lightbulb-fill text-warning fs-5"></i>
                    <span class="fs-6 fw-bold text-dark">Recommended for You (Performance Insights)</span>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Smart Recommendation</span>
            </div>
            <div class="p-3">
                <?php if (empty($recommendations)): ?>
                    <div class="alert alert-success d-flex align-items-center gap-3 mb-0" role="alert">
                        <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Excellent Performance!</h6>
                            <p class="mb-0 small text-dark">You have scored above 50% across all aptitude topics. Keep practicing to maintain your speed and accuracy for company rounds!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($recommendations as $rec): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border <?php echo ($rec['type'] === 'weak') ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : 'border-warning-subtle bg-warning-subtle bg-opacity-10'; ?> p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge <?php echo ($rec['type'] === 'weak') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                            <?php echo ($rec['type'] === 'weak') ? 'Needs Improvement (<50%)' : 'Not Attempted'; ?>
                                        </span>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($rec['topic']); ?></span>
                                    </div>
                                    <p class="small text-muted mb-3 flex-grow-1">
                                        <?php echo htmlspecialchars($rec['message']); ?>
                                    </p>
                                    <a href="test.php?topic=<?php echo urlencode($rec['topic']); ?>" class="btn btn-sm <?php echo ($rec['type'] === 'weak') ? 'btn-danger' : 'btn-warning text-dark'; ?> fw-semibold">
                                        <i class="bi bi-play-circle-fill me-1"></i> Practice <?php echo htmlspecialchars($rec['topic']); ?> Now
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- =======================================================
     Progress Chart & Recent Solved
     ======================================================= -->
<div class="row g-4 mb-4">
    <!-- Chart.js Performance Visualization -->
    <div class="col-lg-7">
        <div class="content-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fs-6 fw-bold">Topic-wise Performance Overview</span>
                <span class="small text-muted">Average Percentage</span>
            </div>
            <div class="p-3">
                <canvas id="topicPerformanceChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Practice Shortcuts -->
    <div class="col-lg-5">
        <div class="content-card h-100">
            <div class="card-header fs-6 fw-bold">
                Quick Placement Modules
            </div>
            <div class="p-3">
                <div class="list-group list-group-flush">
                    <a href="aptitude.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon blue" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-journal-check"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Take an Aptitude Test</h6>
                                <span class="small text-muted">Quant, Logical & Verbal MCQs</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="coding.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon green" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-code-square"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Practice Coding Problems</h6>
                                <span class="small text-muted">Data structures & algorithms</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="company.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon yellow" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-buildings"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Company-wise Questions</h6>
                                <span class="small text-muted">TCS, Infosys, Wipro, Capgemini</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="resume.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon purple" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-file-earmark-person"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Generate ATS Resume</h6>
                                <span class="small text-muted">Clean single-page PDF format</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =======================================================
     Recent Test History Table
     ======================================================= -->
<div class="content-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fs-6 fw-bold">Recent Test Attempts</span>
        <a href="aptitude.php" class="btn btn-sm btn-outline-primary">Start New Test</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Topic</th>
                    <th>Questions</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent_tests_res) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($recent_tests_res)): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['topic']); ?></td>
                            <td><?php echo $row['total_questions']; ?></td>
                            <td><strong><?php echo $row['score']; ?></strong> / <?php echo $row['total_questions']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; width: 80px;">
                                        <div class="progress-bar <?php echo ($row['percentage'] >= 50) ? 'bg-success' : 'bg-danger'; ?>" style="width: <?php echo $row['percentage']; ?>%;"></div>
                                    </div>
                                    <span class="small fw-semibold"><?php echo $row['percentage']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['percentage'] >= 50): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Needs Practice</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?php echo date('d M Y, h:i A', strtotime($row['taken_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No tests taken yet. <a href="aptitude.php">Take your first test now!</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Script Configuration -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('topicPerformanceChart').getContext('2d');
    
    const chartData = {
        labels: ['Quantitative', 'Logical Reasoning', 'Verbal Ability'],
        datasets: [{
            label: 'Average Score (%)',
            data: [
                <?php echo $topic_scores['Quantitative']['avg']; ?>,
                <?php echo $topic_scores['Logical']['avg']; ?>,
                <?php echo $topic_scores['Verbal']['avg']; ?>
            ],
            backgroundColor: [
                'rgba(13, 110, 253, 0.7)',
                'rgba(25, 135, 84, 0.7)',
                'rgba(255, 193, 7, 0.7)'
            ],
            borderColor: [
                '#0d6efd',
                '#198754',
                '#ffc107'
            ],
            borderWidth: 1.5,
            borderRadius: 6
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) { return value + "%"; }
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php
// Close main content and dashboard wrapper before footer
?>
</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
