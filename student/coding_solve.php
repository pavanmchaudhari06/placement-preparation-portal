<?php
/**
 * Student Coding Workspace: coding_solve.php
 * Displays problem statement, sample inputs/outputs, code box, and solved toggle.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$user = get_logged_user();
$student_id = $user['id'];

$problem_id = intval($_GET['id'] ?? 0);

// Fetch problem details
$prob_query = "SELECT * FROM coding_problems WHERE id = $problem_id LIMIT 1";
$prob_res = mysqli_query($conn, $prob_query);

if (!$prob_res || mysqli_num_rows($prob_res) === 0) {
    header("Location: coding.php");
    exit;
}

$problem = mysqli_fetch_assoc($prob_res);

// Check if currently solved
$check_solved_query = "SELECT id FROM solved_problems WHERE user_id = $student_id AND problem_id = $problem_id LIMIT 1";
$check_solved_res = mysqli_query($conn, $check_solved_query);
$is_solved = (mysqli_num_rows($check_solved_res) > 0);

$message = '';

// Handle Mark as Solved / Unsolved Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'solve') {
        $insert = "INSERT IGNORE INTO solved_problems (user_id, problem_id) VALUES ($student_id, $problem_id)";
        if (mysqli_query($conn, $insert)) {
            $is_solved = true;
            $message = "Congratulations! Problem marked as solved successfully.";
        }
    } elseif ($_POST['action'] === 'unsolve') {
        $delete = "DELETE FROM solved_problems WHERE user_id = $student_id AND problem_id = $problem_id";
        if (mysqli_query($conn, $delete)) {
            $is_solved = false;
            $message = "Problem marked as unsolved.";
        }
    }
}

$page_title = $problem['title'] . " - Coding Practice";
$page_heading = "Coding Workspace";
$current_page = "coding";

require_once '../includes/student_header.php';
?>

<div class="mb-3">
    <a href="coding.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Problems List
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Problem Description & Sample Cases -->
    <div class="col-lg-6">
        <div class="content-card h-100 shadow-sm d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fs-5 fw-bold text-dark"><?php echo htmlspecialchars($problem['title']); ?></span>
                <div>
                    <?php if ($problem['difficulty'] === 'Easy'): ?>
                        <span class="badge bg-success">Easy</span>
                    <?php elseif ($problem['difficulty'] === 'Medium'): ?>
                        <span class="badge bg-warning text-dark">Medium</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Hard</span>
                    <?php endif; ?>

                    <?php if ($is_solved): ?>
                        <span class="badge bg-success ms-1"><i class="bi bi-check-lg"></i> Solved</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-1">Unsolved</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-4 flex-grow-1">
                <h6 class="fw-bold text-dark mb-2">Problem Statement:</h6>
                <p class="text-secondary mb-4" style="line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($problem['description'])); ?>
                </p>

                <?php if (!empty($problem['sample_input'])): ?>
                    <h6 class="fw-bold text-dark mb-2">Sample Input:</h6>
                    <pre class="bg-light p-3 rounded border text-dark font-monospace mb-3"><?php echo htmlspecialchars($problem['sample_input']); ?></pre>
                <?php endif; ?>

                <?php if (!empty($problem['sample_output'])): ?>
                    <h6 class="fw-bold text-dark mb-2">Sample Output:</h6>
                    <pre class="bg-light p-3 rounded border text-dark font-monospace mb-4"><?php echo htmlspecialchars($problem['sample_output']); ?></pre>
                <?php endif; ?>

                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> <strong>Viva Tip:</strong> Walk the examiner through your approach, edge cases (e.g. empty inputs, single element), and time complexity.
                </div>
            </div>

            <!-- Solved Toggle Form -->
            <div class="card-footer bg-white p-3 border-top d-flex justify-content-between align-items-center">
                <span class="small text-muted">Finished practicing your logic?</span>
                <form method="POST" action="coding_solve.php?id=<?php echo $problem_id; ?>" class="d-inline">
                    <?php if ($is_solved): ?>
                        <input type="hidden" name="action" value="unsolve">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Mark as Unsolved
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="solve">
                        <button type="submit" class="btn btn-success btn-sm fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i> Mark as Solved
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Code Editor Box -->
    <div class="col-lg-6">
        <div class="content-card h-100 shadow-sm d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-terminal-fill text-warning"></i>
                    <span class="fw-bold">Code Workspace</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="langSelect" class="small text-white-50 mb-0">Language:</label>
                    <select id="langSelect" class="form-select form-select-sm bg-secondary text-white border-0" style="width: 120px;" onchange="updateTemplate()">
                        <option value="cpp">C++</option>
                        <option value="java">Java</option>
                        <option value="python">Python 3</option>
                        <option value="c">C</option>
                    </select>
                </div>
            </div>

            <div class="p-3 bg-dark flex-grow-1 d-flex flex-column">
                <textarea id="codeEditor" class="code-editor-box flex-grow-1" placeholder="Write or paste your code here...">#include <iostream>
using namespace std;

// Function implementation
void solve() {
    // Write your logic here
}

int main() {
    solve();
    return 0;
}</textarea>

                <div class="mt-3 d-flex justify-content-between align-items-center text-white-50 small">
                    <span><i class="bi bi-keyboard me-1"></i> Code workspace for syntax drafting</span>
                    <button class="btn btn-sm btn-outline-light" onclick="copyCode()">
                        <i class="bi bi-clipboard me-1"></i> Copy Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const templates = {
    cpp: `#include <iostream>\nusing namespace std;\n\nvoid solve() {\n    // Write your logic here\n}\n\nint main() {\n    solve();\n    return 0;\n}`,
    java: `import java.util.*;\n\npublic class Solution {\n    public static void main(String[] args) {\n        // Write your logic here\n        System.out.println("Hello World");\n    }\n}`,
    python: `def solve():\n    # Write your logic here\n    pass\n\nif __name__ == "__main__":\n    solve()`,
    c: `#include <stdio.h>\n\nint main() {\n    // Write your logic here\n    return 0;\n}`
};

function updateTemplate() {
    const lang = document.getElementById('langSelect').value;
    document.getElementById('codeEditor').value = templates[lang] || '';
}

function copyCode() {
    const code = document.getElementById('codeEditor').value;
    navigator.clipboard.writeText(code).then(() => {
        alert("Code copied to clipboard!");
    });
}
</script>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
