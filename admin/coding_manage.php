<?php
/**
 * Administrator Coding Problems CRUD: coding_manage.php
 * Add, edit, and delete coding problems.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

require_admin();

$message = '';
$error = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM coding_problems WHERE id = $del_id")) {
        $message = "Coding problem deleted successfully.";
    } else {
        $error = "Failed to delete problem.";
    }
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $difficulty    = trim($_POST['difficulty'] ?? 'Easy');
    $description   = trim($_POST['description'] ?? '');
    $sample_input  = trim($_POST['sample_input'] ?? '');
    $sample_output = trim($_POST['sample_output'] ?? '');
    $edit_id       = intval($_POST['edit_id'] ?? 0);

    if (empty($title) || empty($description)) {
        $error = "Title and problem description are required.";
    } else {
        $safe_title = mysqli_real_escape_string($conn, $title);
        $safe_diff  = mysqli_real_escape_string($conn, $difficulty);
        $safe_desc  = mysqli_real_escape_string($conn, $description);
        $safe_in    = mysqli_real_escape_string($conn, $sample_input);
        $safe_out   = mysqli_real_escape_string($conn, $sample_output);

        if ($edit_id > 0) {
            $sql = "UPDATE coding_problems SET 
                    title = '$safe_title',
                    difficulty = '$safe_diff',
                    description = '$safe_desc',
                    sample_input = '$safe_in',
                    sample_output = '$safe_out'
                    WHERE id = $edit_id";
            if (mysqli_query($conn, $sql)) {
                $message = "Coding problem updated successfully.";
            } else {
                $error = "Update failed: " . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO coding_problems (title, difficulty, description, sample_input, sample_output)
                    VALUES ('$safe_title', '$safe_diff', '$safe_desc', '$safe_in', '$safe_out')";
            if (mysqli_query($conn, $sql)) {
                $message = "New coding problem added successfully.";
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
    $edit_res = mysqli_query($conn, "SELECT * FROM coding_problems WHERE id = $edit_id LIMIT 1");
    if ($edit_res && mysqli_num_rows($edit_res) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_res);
    }
}

// Fetch all problems
$problems_res = mysqli_query($conn, "SELECT * FROM coding_problems ORDER BY FIELD(difficulty, 'Easy', 'Medium', 'Hard'), id ASC");

$page_title = "Manage Coding Problems";
$page_heading = "Coding Problems Management";
$current_page = "coding";

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

<!-- Problem Form Card -->
<div class="card content-card mb-4 shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-code-slash me-1"></i> 
            <?php echo ($edit_data) ? 'Edit Problem #' . $edit_data['id'] : 'Add New Coding Problem'; ?>
        </span>
        <?php if ($edit_data): ?>
            <a href="coding_manage.php" class="btn btn-sm btn-light text-success fw-semibold">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="coding_manage.php">
            <input type="hidden" name="edit_id" value="<?php echo $edit_data['id'] ?? 0; ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Problem Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>" placeholder="e.g. Reverse an Array">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Difficulty Level *</label>
                    <select name="difficulty" class="form-select" required>
                        <option value="Easy" <?php echo (($edit_data['difficulty'] ?? '') === 'Easy') ? 'selected' : ''; ?>>Easy</option>
                        <option value="Medium" <?php echo (($edit_data['difficulty'] ?? '') === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="Hard" <?php echo (($edit_data['difficulty'] ?? '') === 'Hard') ? 'selected' : ''; ?>>Hard</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Problem Description *</label>
                <textarea name="description" class="form-control" rows="4" required placeholder="Detailed problem statement and constraints..."><?php echo htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Sample Input</label>
                    <textarea name="sample_input" class="form-control font-monospace" rows="2" placeholder="e.g. 5&#10;1 2 3 4 5"><?php echo htmlspecialchars($edit_data['sample_input'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Sample Output</label>
                    <textarea name="sample_output" class="form-control font-monospace" rows="2" placeholder="e.g. 5 4 3 2 1"><?php echo htmlspecialchars($edit_data['sample_output'] ?? ''); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-success px-4 fw-semibold">
                <i class="bi bi-save me-1"></i> <?php echo ($edit_data) ? 'Update Problem' : 'Save Problem'; ?>
            </button>
        </form>
    </div>
</div>

<!-- Problems List Table -->
<div class="content-card shadow-sm">
    <div class="card-header fw-bold">
        Existing Coding Challenges (<?php echo mysqli_num_rows($problems_res); ?>)
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Title</th>
                    <th style="width: 110px;">Difficulty</th>
                    <th>Sample Input / Output</th>
                    <th style="width: 140px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($problems_res) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($problems_res)): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($p['title']); ?></td>
                            <td>
                                <?php if ($p['difficulty'] === 'Easy'): ?>
                                    <span class="badge bg-success">Easy</span>
                                <?php elseif ($p['difficulty'] === 'Medium'): ?>
                                    <span class="badge bg-warning text-dark">Medium</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Hard</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-muted font-monospace text-truncate" style="max-width: 320px;">
                                    In: <?php echo htmlspecialchars($p['sample_input'] ?? 'None'); ?> | Out: <?php echo htmlspecialchars($p['sample_output'] ?? 'None'); ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="coding_manage.php?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="coding_manage.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this coding problem?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No coding problems created yet.</td>
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
