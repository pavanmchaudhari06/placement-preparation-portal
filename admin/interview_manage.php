<?php
/**
 * Administrator Interview Questions CRUD: interview_manage.php
 * Add, edit, and delete HR and Technical interview questions.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

require_admin();

$message = '';
$error = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM interview_questions WHERE id = $del_id")) {
        $message = "Interview question deleted successfully.";
    } else {
        $error = "Failed to delete question.";
    }
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? 'HR');
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer'] ?? '');
    $edit_id  = intval($_POST['edit_id'] ?? 0);

    if (empty($question) || empty($answer)) {
        $error = "Both question statement and answer are required.";
    } else {
        $safe_cat = mysqli_real_escape_string($conn, $category);
        $safe_q   = mysqli_real_escape_string($conn, $question);
        $safe_a   = mysqli_real_escape_string($conn, $answer);

        if ($edit_id > 0) {
            $sql = "UPDATE interview_questions SET 
                    category = '$safe_cat',
                    question = '$safe_q',
                    answer = '$safe_a'
                    WHERE id = $edit_id";
            if (mysqli_query($conn, $sql)) {
                $message = "Interview question updated successfully.";
            } else {
                $error = "Update failed: " . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO interview_questions (category, question, answer)
                    VALUES ('$safe_cat', '$safe_q', '$safe_a')";
            if (mysqli_query($conn, $sql)) {
                $message = "New interview question added successfully.";
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
    $edit_res = mysqli_query($conn, "SELECT * FROM interview_questions WHERE id = $edit_id LIMIT 1");
    if ($edit_res && mysqli_num_rows($edit_res) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_res);
    }
}

// Category filter
$cat_filter = trim($_GET['category'] ?? 'All');
$where = ($cat_filter !== 'All') ? "WHERE category = '" . mysqli_real_escape_string($conn, $cat_filter) . "'" : "";
$questions_res = mysqli_query($conn, "SELECT * FROM interview_questions $where ORDER BY id DESC");

$page_title = "Manage Interview Questions";
$page_heading = "Interview Questions Management";
$current_page = "interview";

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

<!-- Question Form Card -->
<div class="card content-card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-chat-quote-fill me-1"></i> 
            <?php echo ($edit_data) ? 'Edit Question #' . $edit_data['id'] : 'Add New Interview Question'; ?>
        </span>
        <?php if ($edit_data): ?>
            <a href="interview_manage.php" class="btn btn-sm btn-light text-primary fw-semibold">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="interview_manage.php<?php echo ($cat_filter !== 'All') ? '?category=' . urlencode($cat_filter) : ''; ?>">
            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id'] ?? 0; ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Question Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="HR" <?php echo (($edit_data['category'] ?? '') === 'HR') ? 'selected' : ''; ?>>HR Behavioral</option>
                        <option value="Technical" <?php echo (($edit_data['category'] ?? '') === 'Technical') ? 'selected' : ''; ?>>Technical Concepts</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Question *</label>
                <input type="text" name="question" class="form-control" required value="<?php echo htmlspecialchars($edit_data['question'] ?? ''); ?>" placeholder="e.g. Tell me about a time you resolved a conflict.">
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Model Answer / Answering Strategy *</label>
                <textarea name="answer" class="form-control" rows="4" required placeholder="Write the recommended response structure..."><?php echo htmlspecialchars($edit_data['answer'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-save me-1"></i> <?php echo ($edit_data) ? 'Update Question' : 'Save Question'; ?>
            </button>
        </form>
    </div>
</div>

<!-- Category Filter & List Table -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="btn-group shadow-sm">
        <a href="interview_manage.php?category=All" class="btn <?php echo ($cat_filter === 'All') ? 'btn-primary' : 'btn-outline-primary'; ?>">All Questions</a>
        <a href="interview_manage.php?category=HR" class="btn <?php echo ($cat_filter === 'HR') ? 'btn-info text-dark' : 'btn-outline-info'; ?>">HR Questions</a>
        <a href="interview_manage.php?category=Technical" class="btn <?php echo ($cat_filter === 'Technical') ? 'btn-success' : 'btn-outline-success'; ?>">Technical Questions</a>
    </div>
</div>

<div class="content-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th style="width: 120px;">Category</th>
                    <th>Question & Answer Preview</th>
                    <th style="width: 140px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($questions_res) > 0): ?>
                    <?php while ($q = mysqli_fetch_assoc($questions_res)): ?>
                        <tr>
                            <td><?php echo $q['id']; ?></td>
                            <td>
                                <span class="badge <?php echo ($q['category'] === 'HR') ? 'bg-info text-dark' : 'bg-success'; ?>">
                                    <?php echo htmlspecialchars($q['category']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($q['question']); ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 550px;">
                                    <?php echo htmlspecialchars($q['answer']); ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="interview_manage.php?edit=<?php echo $q['id']; ?><?php echo ($cat_filter !== 'All') ? '&category=' . urlencode($cat_filter) : ''; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="interview_manage.php?delete=<?php echo $q['id']; ?><?php echo ($cat_filter !== 'All') ? '&category=' . urlencode($cat_filter) : ''; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this interview question?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No interview questions found.</td>
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
