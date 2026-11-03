<?php
/**
 * Administrator Aptitude Questions CRUD: aptitude_manage.php
 * Add, edit, and delete aptitude MCQs.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

require_admin();

$message = '';
$error = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM aptitude_questions WHERE id = $del_id")) {
        $message = "Question deleted successfully.";
    } else {
        $error = "Failed to delete question.";
    }
}

// Handle Add / Edit Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic       = trim($_POST['topic'] ?? 'Quantitative');
    $question    = trim($_POST['question'] ?? '');
    $option_a    = trim($_POST['option_a'] ?? '');
    $option_b    = trim($_POST['option_b'] ?? '');
    $option_c    = trim($_POST['option_c'] ?? '');
    $option_d    = trim($_POST['option_d'] ?? '');
    $correct_opt = strtoupper(trim($_POST['correct_option'] ?? 'A'));
    $explanation = trim($_POST['explanation'] ?? '');
    $edit_id     = intval($_POST['edit_id'] ?? 0);

    if (empty($question) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error = "Please fill in all question fields and options.";
    } else {
        $safe_topic = mysqli_real_escape_string($conn, $topic);
        $safe_q     = mysqli_real_escape_string($conn, $question);
        $safe_a     = mysqli_real_escape_string($conn, $option_a);
        $safe_b     = mysqli_real_escape_string($conn, $option_b);
        $safe_c     = mysqli_real_escape_string($conn, $option_c);
        $safe_d     = mysqli_real_escape_string($conn, $option_d);
        $safe_corr  = mysqli_real_escape_string($conn, $correct_opt);
        $safe_exp   = mysqli_real_escape_string($conn, $explanation);

        if ($edit_id > 0) {
            // Update
            $sql = "UPDATE aptitude_questions SET 
                    topic = '$safe_topic',
                    question = '$safe_q',
                    option_a = '$safe_a',
                    option_b = '$safe_b',
                    option_c = '$safe_c',
                    option_d = '$safe_d',
                    correct_option = '$safe_corr',
                    explanation = '$safe_exp'
                    WHERE id = $edit_id";
            if (mysqli_query($conn, $sql)) {
                $message = "Question updated successfully.";
            } else {
                $error = "Update failed: " . mysqli_error($conn);
            }
        } else {
            // Insert
            $sql = "INSERT INTO aptitude_questions (topic, question, option_a, option_b, option_c, option_d, correct_option, explanation)
                    VALUES ('$safe_topic', '$safe_q', '$safe_a', '$safe_b', '$safe_c', '$safe_d', '$safe_corr', '$safe_exp')";
            if (mysqli_query($conn, $sql)) {
                $message = "New question added successfully.";
            } else {
                $error = "Insert failed: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch record for editing if requested
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_res = mysqli_query($conn, "SELECT * FROM aptitude_questions WHERE id = $edit_id LIMIT 1");
    if ($edit_res && mysqli_num_rows($edit_res) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_res);
    }
}

// Filter by topic if selected
$filter = trim($_GET['topic'] ?? 'All');
$where = ($filter !== 'All') ? "WHERE topic = '" . mysqli_real_escape_string($conn, $filter) . "'" : "";
$questions_res = mysqli_query($conn, "SELECT * FROM aptitude_questions $where ORDER BY id DESC");

$page_title = "Manage Aptitude Questions";
$page_heading = "Aptitude Questions Management";
$current_page = "aptitude";

require_once '../includes/admin_header.php';
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Question Add / Edit Form Card -->
<div class="card content-card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-pencil-square me-1"></i> 
            <?php echo ($edit_data) ? 'Edit Question #' . $edit_data['id'] : 'Add New Aptitude Question'; ?>
        </span>
        <?php if ($edit_data): ?>
            <a href="aptitude_manage.php" class="btn btn-sm btn-light text-primary fw-semibold">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="aptitude_manage.php<?php echo ($filter !== 'All') ? '?topic=' . urlencode($filter) : ''; ?>">
            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id'] ?? 0; ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Topic Category *</label>
                    <select name="topic" class="form-select" required>
                        <option value="Quantitative" <?php echo (($edit_data['topic'] ?? '') === 'Quantitative') ? 'selected' : ''; ?>>Quantitative Aptitude</option>
                        <option value="Logical" <?php echo (($edit_data['topic'] ?? '') === 'Logical') ? 'selected' : ''; ?>>Logical Reasoning</option>
                        <option value="Verbal" <?php echo (($edit_data['topic'] ?? '') === 'Verbal') ? 'selected' : ''; ?>>Verbal Ability</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Correct Option *</label>
                    <select name="correct_option" class="form-select" required>
                        <option value="A" <?php echo (($edit_data['correct_option'] ?? '') === 'A') ? 'selected' : ''; ?>>Option A</option>
                        <option value="B" <?php echo (($edit_data['correct_option'] ?? '') === 'B') ? 'selected' : ''; ?>>Option B</option>
                        <option value="C" <?php echo (($edit_data['correct_option'] ?? '') === 'C') ? 'selected' : ''; ?>>Option C</option>
                        <option value="D" <?php echo (($edit_data['correct_option'] ?? '') === 'D') ? 'selected' : ''; ?>>Option D</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Question Statement *</label>
                <textarea name="question" class="form-control" rows="3" required placeholder="Type the question text here..."><?php echo htmlspecialchars($edit_data['question'] ?? ''); ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Option A *</label>
                    <input type="text" name="option_a" class="form-control" required value="<?php echo htmlspecialchars($edit_data['option_a'] ?? ''); ?>" placeholder="Choice A">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Option B *</label>
                    <input type="text" name="option_b" class="form-control" required value="<?php echo htmlspecialchars($edit_data['option_b'] ?? ''); ?>" placeholder="Choice B">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Option C *</label>
                    <input type="text" name="option_c" class="form-control" required value="<?php echo htmlspecialchars($edit_data['option_c'] ?? ''); ?>" placeholder="Choice C">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Option D *</label>
                    <input type="text" name="option_d" class="form-control" required value="<?php echo htmlspecialchars($edit_data['option_d'] ?? ''); ?>" placeholder="Choice D">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Explanation / Solution Steps</label>
                <textarea name="explanation" class="form-control" rows="2" placeholder="Explain the rationale behind the correct answer..."><?php echo htmlspecialchars($edit_data['explanation'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-save me-1"></i> <?php echo ($edit_data) ? 'Update Question' : 'Save Question'; ?>
            </button>
        </form>
    </div>
</div>

<!-- Questions Filter & Table -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div class="btn-group shadow-sm">
        <a href="aptitude_manage.php?topic=All" class="btn <?php echo ($filter === 'All') ? 'btn-primary' : 'btn-outline-primary'; ?>">All (<?php echo mysqli_num_rows(mysqli_query($conn, "SELECT id FROM aptitude_questions")); ?>)</a>
        <a href="aptitude_manage.php?topic=Quantitative" class="btn <?php echo ($filter === 'Quantitative') ? 'btn-primary' : 'btn-outline-primary'; ?>">Quant</a>
        <a href="aptitude_manage.php?topic=Logical" class="btn <?php echo ($filter === 'Logical') ? 'btn-primary' : 'btn-outline-primary'; ?>">Logical</a>
        <a href="aptitude_manage.php?topic=Verbal" class="btn <?php echo ($filter === 'Verbal') ? 'btn-primary' : 'btn-outline-primary'; ?>">Verbal</a>
    </div>
</div>

<div class="content-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th style="width: 120px;">Topic</th>
                    <th>Question</th>
                    <th style="width: 90px;">Correct</th>
                    <th style="width: 140px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($questions_res) > 0): ?>
                    <?php while ($q = mysqli_fetch_assoc($questions_res)): ?>
                        <tr>
                            <td><?php echo $q['id']; ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($q['topic']); ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 500px;">
                                    <?php echo htmlspecialchars($q['question']); ?>
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 500px;">
                                    A: <?php echo htmlspecialchars($q['option_a']); ?> | B: <?php echo htmlspecialchars($q['option_b']); ?> | C: <?php echo htmlspecialchars($q['option_c']); ?> | D: <?php echo htmlspecialchars($q['option_d']); ?>
                                </div>
                            </td>
                            <td><span class="badge bg-success fs-6"><?php echo $q['correct_option']; ?></span></td>
                            <td class="text-end">
                                <a href="aptitude_manage.php?edit=<?php echo $q['id']; ?><?php echo ($filter !== 'All') ? '&topic=' . urlencode($filter) : ''; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="aptitude_manage.php?delete=<?php echo $q['id']; ?><?php echo ($filter !== 'All') ? '&topic=' . urlencode($filter) : ''; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this question?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No questions found in this category.</td>
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
