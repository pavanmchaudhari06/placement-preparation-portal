<?php
/**
 * Student Aptitude Module: aptitude.php
 * Displays topics (Quantitative, Logical, Verbal) and allows launching a test.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();
$user = get_logged_user();
$student_id = $user['id'];

// Get question counts and student's best score for each topic
$topic_stats = [];
$topics = ['Quantitative', 'Logical', 'Verbal'];

foreach ($topics as $t) {
    // Total questions in database
    $q_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM aptitude_questions WHERE topic = '$t'");
    $count = mysqli_fetch_assoc($q_res)['count'] ?? 0;

    // Student's highest score percentage
    $s_res = mysqli_query($conn, "SELECT MAX(percentage) as best_score, COUNT(*) as attempts FROM test_results WHERE user_id = $student_id AND topic = '$t'");
    $s_data = mysqli_fetch_assoc($s_res);

    $topic_stats[$t] = [
        'count' => $count,
        'best' => ($s_data['attempts'] > 0) ? round($s_data['best_score'], 1) : null,
        'attempts' => $s_data['attempts'] ?? 0
    ];
}

$page_title = "Aptitude Tests";
$page_heading = "Aptitude Assessment";
$current_page = "aptitude";

require_once '../includes/student_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card content-card border-0 bg-primary text-white p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-clock-history me-2"></i> Timed Aptitude Practice</h4>
                    <p class="mb-0 text-white-50">
                        Choose a topic below to begin a timed MCQ test. Tests evaluate your speed, accuracy, and core problem-solving required for campus placement screening rounds.
                    </p>
                </div>
                <div class="text-md-end">
                    <span class="badge bg-white text-primary px-3 py-2 fs-6">
                        <i class="bi bi-shield-check me-1"></i> Negative Marking: None
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Topic Cards -->
<div class="row g-4 mb-4">
    <!-- Quantitative Aptitude -->
    <div class="col-md-4">
        <div class="stat-card h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon blue">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        <?php echo $topic_stats['Quantitative']['count']; ?> Questions
                    </span>
                </div>
                <h5 class="fw-bold text-dark">Quantitative Aptitude</h5>
                <p class="text-muted small">
                    Arithmetic, Speed, Time & Work, Percentages, Profit & Loss, Simple Interest, Ratios and Averages.
                </p>
                <div class="bg-light p-2 rounded small mb-3">
                    <strong>Your Best:</strong> 
                    <?php if ($topic_stats['Quantitative']['best'] !== null): ?>
                        <span class="text-success fw-bold"><?php echo $topic_stats['Quantitative']['best']; ?>%</span> 
                        <span class="text-muted">(<?php echo $topic_stats['Quantitative']['attempts']; ?> attempts)</span>
                    <?php else: ?>
                        <span class="text-muted">Not attempted</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="test.php?topic=Quantitative" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-play-fill me-1"></i> Start Quant Test
            </a>
        </div>
    </div>

    <!-- Logical Reasoning -->
    <div class="col-md-4">
        <div class="stat-card h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon green">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <?php echo $topic_stats['Logical']['count']; ?> Questions
                    </span>
                </div>
                <h5 class="fw-bold text-dark">Logical Reasoning</h5>
                <p class="text-muted small">
                    Number Series, Blood Relations, Syllogisms, Coding-Decoding, Analogies, and Deductive Logic.
                </p>
                <div class="bg-light p-2 rounded small mb-3">
                    <strong>Your Best:</strong> 
                    <?php if ($topic_stats['Logical']['best'] !== null): ?>
                        <span class="text-success fw-bold"><?php echo $topic_stats['Logical']['best']; ?>%</span> 
                        <span class="text-muted">(<?php echo $topic_stats['Logical']['attempts']; ?> attempts)</span>
                    <?php else: ?>
                        <span class="text-muted">Not attempted</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="test.php?topic=Logical" class="btn btn-success w-100 fw-semibold">
                <i class="bi bi-play-fill me-1"></i> Start Logical Test
            </a>
        </div>
    </div>

    <!-- Verbal Ability -->
    <div class="col-md-4">
        <div class="stat-card h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon yellow">
                        <i class="bi bi-spellcheck"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                        <?php echo $topic_stats['Verbal']['count']; ?> Questions
                    </span>
                </div>
                <h5 class="fw-bold text-dark">Verbal Ability</h5>
                <p class="text-muted small">
                    Synonyms, Antonyms, Sentence Correction, Error Detection, Grammar Rules, and Vocabulary.
                </p>
                <div class="bg-light p-2 rounded small mb-3">
                    <strong>Your Best:</strong> 
                    <?php if ($topic_stats['Verbal']['best'] !== null): ?>
                        <span class="text-success fw-bold"><?php echo $topic_stats['Verbal']['best']; ?>%</span> 
                        <span class="text-muted">(<?php echo $topic_stats['Verbal']['attempts']; ?> attempts)</span>
                    <?php else: ?>
                        <span class="text-muted">Not attempted</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="test.php?topic=Verbal" class="btn btn-warning w-100 fw-semibold text-dark">
                <i class="bi bi-play-fill me-1"></i> Start Verbal Test
            </a>
        </div>
    </div>
</div>

<!-- Test Instructions & Rules -->
<div class="card content-card">
    <div class="card-header fw-bold">
        <i class="bi bi-info-circle-fill text-primary me-2"></i> Standard Placement Test Instructions
    </div>
    <div class="card-body">
        <ul class="mb-0 text-muted">
            <li class="mb-2">Each test consists of standard multiple choice questions (MCQs) curated from placement question banks.</li>
            <li class="mb-2">There is a <strong>10-minute timer</strong> displayed on top of the test screen.</li>
            <li class="mb-2">Each question carries <strong>1 point</strong>. There is no negative marking for incorrect or skipped answers.</li>
            <li class="mb-2">Click <strong>Submit Test</strong> when you finish. An instant scorecard with detailed step-by-step explanations will be shown.</li>
            <li>Your test result is automatically tracked to provide personalized weak-topic recommendations on your dashboard.</li>
        </ul>
    </div>
</div>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
