<?php
/**
 * Administrator Company Questions CRUD: company_manage.php
 * Add, edit, and delete recruiter-specific questions.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

require_admin();

$message = '';
$error = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM company_questions WHERE id = $del_id")) {
        $message = "Company question deleted successfully.";
    } else {
        $error = "Failed to delete question.";
    }
}

// Handle Add / Edit Submission
<?php
    // Handle Add / Edit Submission with validation and prepared statements
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $company_name = trim($_POST['company_name'] ?? '');
        $topic        = trim($_POST['topic'] ?? '');
        $question     = trim($_POST['question'] ?? '');
        $answer       = trim($_POST['answer'] ?? '');
        $difficulty   = trim($_POST['difficulty'] ?? 'Beginner');
        $edit_id      = intval($_POST['edit_id'] ?? 0);

        // Simple server‑side validation
        $allowedDiff = ['Beginner', 'Intermediate', 'Advanced', 'Pro'];
        if (empty($company_name) || empty($topic) || empty($question) || empty($answer) || !in_array($difficulty, $allowedDiff)) {
            $error = 'Please fill in all fields and select a valid difficulty.';
        } else {
            // Use prepared statements for safety
            if ($edit_id > 0) {
                $stmt = $conn->prepare('UPDATE company_questions SET company_name = ?, topic = ?, question = ?, answer = ?, difficulty = ? WHERE id = ?');
                $stmt->bind_param('sssssi', $company_name, $topic, $question, $answer, $difficulty, $edit_id);
                if ($stmt->execute()) {
                    $message = 'Company question updated successfully.';
                } else {
                    $error = 'Update failed.';
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare('INSERT INTO company_questions (company_name, topic, question, answer, difficulty) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $company_name, $topic, $question, $answer, $difficulty);
                if ($stmt->execute()) {
                    $message = 'New company question added successfully.';
                } else {
                    $error = 'Insert failed.';
                }
                $stmt->close();
            }
        }
    }
?>
    $company_name = trim($_POST['company_name'] ?? '');
    $topic        = trim($_POST['topic'] ?? '');
    $question     = trim($_POST['question'] ?? '');
    $answer       = trim($_POST['answer'] ?? '');
    $edit_id      = intval($_POST['edit_id'] ?? 0);

    if (empty($company_name) || empty($topic) || empty($question) || empty($answer)) {
        $error = "Please fill in all company question details.";
    } else {
        $safe_comp  = mysqli_real_escape_string($conn, $company_name);
        $safe_topic = mysqli_real_escape_string($conn, $topic);
        $safe_q     = mysqli_real_escape_string($conn, $question);
        $safe_a     = mysqli_real_escape_string($conn, $answer);

        if ($edit_id > 0) {
            $sql = "UPDATE company_questions SET 
                    company_name = '$safe_comp',
                    topic = '$safe_topic',
                    question = '$safe_q',
                    answer = '$safe_a'
                    WHERE id = $edit_id";
            if (mysqli_query($conn, $sql)) {
                $message = "Company question updated successfully.";
            } else {
                $error = "Update failed: " . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO company_questions (company_name, topic, question, answer)
                    VALUES ('$safe_comp', '$safe_topic', '$safe_q', '$safe_a')";
            if (mysqli_query($conn, $sql)) {
                $message = "New company question added successfully.";
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
    $edit_res = mysqli_query($conn, "SELECT * FROM company_questions WHERE id = $edit_id LIMIT 1");
    if ($edit_res && mysqli_num_rows($edit_res) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_res);
    }
}

// Fetch unique companies for dropdown / filter
$comp_list_res = mysqli_query($conn, "SELECT DISTINCT company_name FROM company_questions ORDER BY company_name ASC");
$all_companies = [];
while ($r = mysqli_fetch_assoc($comp_list_res)) {
    $all_companies[] = $r['company_name'];
}

// Company filter
$filter_comp = trim($_GET['company'] ?? 'All');
$where = ($filter_comp !== 'All') ? "WHERE company_name = '" . mysqli_real_escape_string($conn, $filter_comp) . "'" : "";
$questions_res = mysqli_query($conn, "SELECT * FROM company_questions $where ORDER BY id DESC");

$page_title = "Manage Company Questions";
$page_heading = "Company Questions Management";
$current_page = "company";

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

<!-- Company Question Form Card -->
<div class="card content-card mb-4 shadow-sm">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-buildings-fill me-1"></i> 
            <?php echo ($edit_data) ? 'Edit Question #' . $edit_data['id'] : 'Add New Company Question'; ?>
        </span>
        <?php if ($edit_data): ?>
            <a href="company_manage.php" class="btn btn-sm btn-dark fw-semibold">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="company_manage.php<?php echo ($filter_comp !== 'All') ? '?company=' . urlencode($filter_comp) : ''; ?>">
            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id'] ?? 0; ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Company Name *</label>
                    <input type="text" name="company_name" class="form-control" list="companySuggestions" required value="<?php echo htmlspecialchars($edit_data['company_name'] ?? ''); ?>" placeholder="e.g. TCS, Infosys, Wipro, Capgemini">
                    <datalist id="companySuggestions">
                        <option value="TCS">
                        <option value="Infosys">
                        <option value="Wipro">
                        <option value="Capgemini">
                        <option value="Accenture">
                        <option value="Cognizant">
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Interview Round / Module *</label>
                    <input type="text" name="topic" class="form-control" required value="<?php echo htmlspecialchars($edit_data['topic'] ?? ''); ?>" placeholder="e.g. Technical Round, NQT Coding, Pseudocode">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Question Statement *</label>
                <textarea name="question" class="form-control" rows="2" required placeholder="Question asked in the test or interview..."><?php echo htmlspecialchars($edit_data['question'] ?? ''); ?></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Answer / Solution Logic *</label>
                <textarea name="answer" class="form-control" rows="3" required placeholder="Detailed solution, explanation or answering tips..."><?php echo htmlspecialchars($edit_data['answer'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Difficulty *</label>
                <select name="difficulty" class="form-select" required>
                    <option value="Beginner" <?= (isset($edit_data['difficulty']) && $edit_data['difficulty'] === 'Beginner') ? 'selected' : '' ?>>Beginner</option>
                    <option value="Intermediate" <?= (isset($edit_data['difficulty']) && $edit_data['difficulty'] === 'Intermediate') ? 'selected' : '' ?>>Intermediate</option>
                    <option value="Advanced" <?= (isset($edit_data['difficulty']) && $edit_data['difficulty'] === 'Advanced') ? 'selected' : '' ?>>Advanced</option>
                    <option value="Pro" <?= (isset($edit_data['difficulty']) && $edit_data['difficulty'] === 'Pro') ? 'selected' : '' ?>>Pro</option>
                </select>
            </div>

            <button type="submit" class="btn btn-warning px-4 fw-bold">
                <i class="bi bi-save me-1"></i> <?php echo ($edit_data) ? 'Update Question' : 'Save Company Question'; ?>
            </button>
        </form>
    </div>
</div>

<!-- Company Filter & List Table -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <span class="small fw-bold text-muted">Filter by Company:</span>
    <a href="company_manage.php?company=All" class="btn btn-sm <?php echo ($filter_comp === 'All') ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
    <?php foreach ($all_companies as $c): ?>
        <a href="company_manage.php?company=<?php echo urlencode($c); ?>" class="btn btn-sm <?php echo ($filter_comp === $c) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo htmlspecialchars($c); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="content-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th style="width: 130px;">Company</th>
                    <th style="width: 160px;">Round</th>
                    <th style="width: 110px;">Difficulty</th>
                    <th>Question</th>
                    <th style="width: 140px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($questions_res) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($questions_res)): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($item['company_name']); ?></span></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($item['topic']); ?></span></td>
<td><span class="badge <?php echo ($item['difficulty'] === 'Beginner') ? 'bg-success' : ($item['difficulty'] === 'Intermediate') ? 'bg-primary' : ($item['difficulty'] === 'Advanced') ? 'bg-warning' : 'bg-danger'; ?>"><?php echo htmlspecialchars($item['difficulty']); ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($item['question']); ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 500px;">
                                    <?php echo htmlspecialchars($item['answer']); ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="company_manage.php?edit=<?php echo $item['id']; ?><?php echo ($filter_comp !== 'All') ? '&company=' . urlencode($filter_comp) : ''; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="company_manage.php?delete=<?php echo $item['id']; ?><?php echo ($filter_comp !== 'All') ? '&company=' . urlencode($filter_comp) : ''; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this company question?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No questions found for this company.</td>
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
