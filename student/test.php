<?php
/**
 * Student Aptitude Test Interface: test.php
 * Displays MCQs for the chosen topic with a live countdown timer and radio selections.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$topic = trim($_GET['topic'] ?? 'Quantitative');
$allowed_topics = ['Quantitative', 'Logical', 'Verbal'];

if (!in_array($topic, $allowed_topics)) {
    $topic = 'Quantitative';
}

$safe_topic = mysqli_real_escape_string($conn, $topic);
$query = "SELECT * FROM aptitude_questions WHERE topic = '$safe_topic' ORDER BY id ASC";
$result = mysqli_query($conn, $query);

$questions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $questions[] = $row;
}

$total_q = count($questions);

$page_title = $topic . " Test";
$page_heading = "Aptitude Assessment: " . $topic;
$current_page = "aptitude";

require_once '../includes/student_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card p-3 d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white">
            <div>
                <h5 class="fw-bold text-dark mb-0">Topic: <?php echo htmlspecialchars($topic); ?></h5>
                <span class="small text-muted">Total Questions: <?php echo $total_q; ?> | 1 mark per question</span>
            </div>
            
            <!-- Live Countdown Timer -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small fw-bold">TIME REMAINING:</span>
                <span id="timerBadge" class="badge bg-danger fs-6 px-3 py-2">
                    <i class="bi bi-clock-history me-1"></i> <span id="timeDisplay">10:00</span>
                </span>
            </div>
        </div>
    </div>
</div>

<?php if ($total_q === 0): ?>
    <div class="alert alert-warning">
        <h5>No questions found for this topic!</h5>
        <p>Please ask the Administrator to add questions in this category or choose another topic.</p>
        <a href="aptitude.php" class="btn btn-secondary">Back to Topics</a>
    </div>
<?php else: ?>

    <form id="testForm" action="test_submit.php" method="POST">
        <input type="hidden" name="topic" value="<?php echo htmlspecialchars($topic); ?>">
        <input type="hidden" name="total_questions" value="<?php echo $total_q; ?>">

        <?php foreach ($questions as $index => $q): ?>
            <div class="card content-card mb-4 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">Question <?php echo ($index + 1); ?> of <?php echo $total_q; ?></span>
                    <span class="badge bg-secondary-subtle text-secondary">1 Mark</span>
                </div>
                <div class="card-body p-4">
                    <p class="fs-6 fw-semibold text-dark mb-4">
                        <?php echo nl2br(htmlspecialchars($q['question'])); ?>
                    </p>

                    <div class="list-group">
                        <!-- Option A -->
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3">
                            <input class="form-check-input flex-shrink-0" type="radio" name="answers[<?php echo $q['id']; ?>]" value="A">
                            <div>
                                <span class="fw-bold me-2">A.</span>
                                <span><?php echo htmlspecialchars($q['option_a']); ?></span>
                            </div>
                        </label>

                        <!-- Option B -->
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3">
                            <input class="form-check-input flex-shrink-0" type="radio" name="answers[<?php echo $q['id']; ?>]" value="B">
                            <div>
                                <span class="fw-bold me-2">B.</span>
                                <span><?php echo htmlspecialchars($q['option_b']); ?></span>
                            </div>
                        </label>

                        <!-- Option C -->
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3">
                            <input class="form-check-input flex-shrink-0" type="radio" name="answers[<?php echo $q['id']; ?>]" value="C">
                            <div>
                                <span class="fw-bold me-2">C.</span>
                                <span><?php echo htmlspecialchars($q['option_c']); ?></span>
                            </div>
                        </label>

                        <!-- Option D -->
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3">
                            <input class="form-check-input flex-shrink-0" type="radio" name="answers[<?php echo $q['id']; ?>]" value="D">
                            <div>
                                <span class="fw-bold me-2">D.</span>
                                <span><?php echo htmlspecialchars($q['option_d']); ?></span>
                            </div>
                        </label>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>

        <!-- Submit Bar -->
        <div class="card content-card p-3 mb-5 d-flex flex-row justify-content-between align-items-center">
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1"></i> Make sure to review all answers before submitting.
            </span>
            <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold" onclick="return confirm('Are you sure you want to submit your test?');">
                <i class="bi bi-check-circle-fill me-2"></i> Submit Test
            </button>
        </div>
    </form>

    <!-- Countdown Timer Script -->
    <script>
    let timeLeft = 600; // 10 minutes in seconds (600 seconds)
    const timeDisplay = document.getElementById('timeDisplay');
    const timerBadge = document.getElementById('timerBadge');

    const countdown = setInterval(function() {
        timeLeft--;

        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;

        if (minutes < 10) minutes = "0" + minutes;
        if (seconds < 10) seconds = "0" + seconds;

        timeDisplay.textContent = minutes + ":" + seconds;

        // Change badge color when under 2 minutes
        if (timeLeft <= 120) {
            timerBadge.classList.remove('bg-danger');
            timerBadge.classList.add('bg-danger', 'animate-pulse');
        }

        // Time expired: auto submit
        if (timeLeft <= 0) {
            clearInterval(countdown);
            alert("Time is up! Your answers will be submitted automatically.");
            document.getElementById('testForm').submit();
        }
    }, 1000);
    </script>

<?php endif; ?>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
