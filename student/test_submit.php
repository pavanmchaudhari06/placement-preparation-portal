<?php
/**
 * Test Submission and Evaluation: test_submit.php
 * Scores student submission, stores test_results in database, and renders detailed review.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$user = get_logged_user();
$student_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: aptitude.php");
    exit;
}

$topic = trim($_POST['topic'] ?? 'Quantitative');
$submitted_answers = $_POST['answers'] ?? []; // Array [question_id => 'A'|'B'|'C'|'D']

$safe_topic = mysqli_real_escape_string($conn, $topic);

// Fetch questions for this topic to evaluate
$query = "SELECT * FROM aptitude_questions WHERE topic = '$safe_topic' ORDER BY id ASC";
$result = mysqli_query($conn, $query);

$total_questions = 0;
$score = 0;
$review = [];

while ($q = mysqli_fetch_assoc($result)) {
    $total_questions++;
    $qid = $q['id'];
    $selected = $submitted_answers[$qid] ?? null;
    $is_correct = ($selected !== null && strtoupper($selected) === strtoupper($q['correct_option']));

    if ($is_correct) {
        $score++;
    }

    $review[] = [
        'id'             => $qid,
        'question'       => $q['question'],
        'option_a'       => $q['option_a'],
        'option_b'       => $q['option_b'],
        'option_c'       => $q['option_c'],
        'option_d'       => $q['option_d'],
        'correct_option' => $q['correct_option'],
        'selected'       => $selected,
        'is_correct'     => $is_correct,
        'explanation'    => $q['explanation']
    ];
}

$percentage = ($total_questions > 0) ? round(($score / $total_questions) * 100, 2) : 0;

// Insert result into test_results table
$insert_query = "INSERT INTO test_results (user_id, topic, total_questions, score, percentage) 
                 VALUES ($student_id, '$safe_topic', $total_questions, $score, $percentage)";
mysqli_query($conn, $insert_query);

$page_title = "Test Results - " . $topic;
$page_heading = "Assessment Scorecard";
$current_page = "aptitude";

require_once '../includes/student_header.php';
?>

<!-- Score Overview Card -->
<div class="card content-card mb-4 border-0 shadow-sm overflow-hidden">
    <div class="p-4 <?php echo ($percentage >= 50) ? 'bg-success-subtle border-start border-success border-5' : 'bg-danger-subtle border-start border-danger border-5'; ?>">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge <?php echo ($percentage >= 50) ? 'bg-success' : 'bg-danger'; ?> px-3 py-2 fs-6 mb-2">
                    <?php echo ($percentage >= 50) ? 'Assessment Passed' : 'Needs Practice (Score < 50%)'; ?>
                </span>
                <h3 class="fw-bold text-dark mb-1">
                    Topic: <?php echo htmlspecialchars($topic); ?>
                </h3>
                <p class="text-muted mb-0">
                    <?php if ($percentage >= 50): ?>
                        Great performance! You demonstrated good command of <?php echo htmlspecialchars($topic); ?> concepts.
                    <?php else: ?>
                        Your score is below 50%. This topic has been updated in your <strong>"Recommended for You"</strong> section on the dashboard for targeted improvement.
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="display-5 fw-bold <?php echo ($percentage >= 50) ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $score; ?> / <?php echo $total_questions; ?>
                </div>
                <div class="fs-5 fw-semibold text-secondary"><?php echo $percentage; ?>% Score</div>
            </div>
        </div>
    </div>
</div>

<!-- Action Shortcuts -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="test.php?topic=<?php echo urlencode($topic); ?>" class="btn btn-outline-primary">
        <i class="bi bi-arrow-repeat me-1"></i> Retake Test
    </a>
    <a href="aptitude.php" class="btn btn-outline-secondary">
        <i class="bi bi-grid-fill me-1"></i> Other Topics
    </a>
    <a href="dashboard.php" class="btn btn-primary">
        <i class="bi bi-speedometer2 me-1"></i> Back to Dashboard
    </a>
</div>

<!-- Detailed Question-by-Question Review -->
<h5 class="fw-bold text-dark mb-3">
    <i class="bi bi-card-checklist text-primary me-2"></i> Question-by-Question Detailed Review
</h5>

<?php foreach ($review as $idx => $r): ?>
    <div class="card content-card mb-3 border <?php echo ($r['is_correct']) ? 'border-success-subtle' : 'border-danger-subtle'; ?>">
        <div class="card-header d-flex justify-content-between align-items-center <?php echo ($r['is_correct']) ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'; ?>">
            <span class="fw-bold">
                Question <?php echo ($idx + 1); ?>
            </span>
            <span>
                <?php if ($r['is_correct']): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Correct (+1)</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> Incorrect (0)</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <p class="fw-semibold text-dark mb-3">
                <?php echo nl2br(htmlspecialchars($r['question'])); ?>
            </p>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <div class="p-2 border rounded <?php echo ($r['correct_option'] === 'A') ? 'bg-success text-white' : (($r['selected'] === 'A' && !$r['is_correct']) ? 'bg-danger text-white' : 'bg-light'); ?>">
                        <strong>A:</strong> <?php echo htmlspecialchars($r['option_a']); ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-2 border rounded <?php echo ($r['correct_option'] === 'B') ? 'bg-success text-white' : (($r['selected'] === 'B' && !$r['is_correct']) ? 'bg-danger text-white' : 'bg-light'); ?>">
                        <strong>B:</strong> <?php echo htmlspecialchars($r['option_b']); ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-2 border rounded <?php echo ($r['correct_option'] === 'C') ? 'bg-success text-white' : (($r['selected'] === 'C' && !$r['is_correct']) ? 'bg-danger text-white' : 'bg-light'); ?>">
                        <strong>C:</strong> <?php echo htmlspecialchars($r['option_c']); ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-2 border rounded <?php echo ($r['correct_option'] === 'D') ? 'bg-success text-white' : (($r['selected'] === 'D' && !$r['is_correct']) ? 'bg-danger text-white' : 'bg-light'); ?>">
                        <strong>D:</strong> <?php echo htmlspecialchars($r['option_d']); ?>
                    </div>
                </div>
            </div>

            <div class="small mb-2">
                <strong>Your Answer:</strong> 
                <?php if ($r['selected']): ?>
                    <span class="badge <?php echo ($r['is_correct']) ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($r['selected']); ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary">Not Answered</span>
                <?php endif; ?>
                &nbsp;|&nbsp;
                <strong>Correct Answer:</strong> 
                <span class="badge bg-success"><?php echo htmlspecialchars($r['correct_option']); ?></span>
            </div>

            <?php if (!empty($r['explanation'])): ?>
                <div class="alert alert-light border small text-dark mt-2 mb-0">
                    <strong><i class="bi bi-lightbulb-fill text-warning me-1"></i> Explanation:</strong>
                    <?php echo nl2br(htmlspecialchars($r['explanation'])); ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php endforeach; ?>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
