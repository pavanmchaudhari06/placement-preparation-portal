<?php
/**
 * Student Interview Preparation: interview.php
 * Displays HR and Technical questions with accordion answers and category filters.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$category_filter = trim($_GET['category'] ?? 'All');
$allowed_cats = ['All', 'HR', 'Technical'];

if (!in_array($category_filter, $allowed_cats)) {
    $category_filter = 'All';
}

$search = trim($_GET['search'] ?? '');

$where_clauses = [];
if ($category_filter !== 'All') {
    $safe_cat = mysqli_real_escape_string($conn, $category_filter);
    $where_clauses[] = "category = '$safe_cat'";
}

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(question LIKE '%$safe_search%' OR answer LIKE '%$safe_search%')";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$query = "SELECT * FROM interview_questions $where_sql ORDER BY FIELD(category, 'HR', 'Technical'), id ASC";
$result = mysqli_query($conn, $query);

$page_title = "Interview Preparation";
$page_heading = "Interview Preparation (HR & Technical)";
$current_page = "interview";

require_once '../includes/student_header.php';
?>

<!-- Header Banner -->
<div class="card content-card border-0 bg-primary text-white p-4 mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-chat-quote-fill me-2"></i> HR & Technical Interview Guide</h4>
    <p class="text-white-50 mb-0">
        Review commonly asked HR behavioural questions and core technical concepts (OOPs, DBMS, Operating Systems, Computer Networks). Click each question to expand the curated answer and model response strategy.
    </p>
</div>

<!-- Category Filters & Search -->
<div class="row g-3 align-items-center justify-content-between mb-4">
    <div class="col-md-6">
        <div class="btn-group shadow-sm">
            <a href="interview.php?category=All<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn <?php echo ($category_filter === 'All') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                All Questions
            </a>
            <a href="interview.php?category=HR<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn <?php echo ($category_filter === 'HR') ? 'btn-info text-dark' : 'btn-outline-info'; ?>">
                <i class="bi bi-person-badge me-1"></i> HR Questions
            </a>
            <a href="interview.php?category=Technical<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn <?php echo ($category_filter === 'Technical') ? 'btn-success' : 'btn-outline-success'; ?>">
                <i class="bi bi-code-slash me-1"></i> Technical Questions
            </a>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <form method="GET" action="interview.php" class="d-flex gap-2">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search questions..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <?php if (!empty($search)): ?>
                <a href="interview.php?category=<?php echo urlencode($category_filter); ?>" class="btn btn-outline-danger" title="Clear Search">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Questions Accordion -->
<div class="accordion shadow-sm" id="interviewAccordion">
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php $i = 0; while ($q = mysqli_fetch_assoc($result)): $i++; ?>
            <div class="accordion-item mb-2 border rounded">
                <h2 class="accordion-header" id="heading<?php echo $q['id']; ?>">
                    <button class="accordion-button <?php echo ($i > 1) ? 'collapsed' : ''; ?> fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $q['id']; ?>" aria-expanded="<?php echo ($i === 1) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $q['id']; ?>">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge <?php echo ($q['category'] === 'HR') ? 'bg-info text-dark' : 'bg-success'; ?>">
                                <?php echo htmlspecialchars($q['category']); ?>
                            </span>
                            <span class="text-dark"><?php echo htmlspecialchars($q['question']); ?></span>
                        </div>
                    </button>
                </h2>
                <div id="collapse<?php echo $q['id']; ?>" class="accordion-collapse collapse <?php echo ($i === 1) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $q['id']; ?>" data-bs-parent="#interviewAccordion">
                    <div class="accordion-body bg-light p-4">
                        <h6 class="fw-bold text-primary mb-2">Recommended Model Answer:</h6>
                        <div class="text-dark" style="line-height: 1.8; white-space: pre-line;">
                            <?php echo htmlspecialchars($q['answer']); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card p-5 text-center text-muted">
            <i class="bi bi-search fs-1 mb-2"></i>
            <h5>No questions found</h5>
            <p class="mb-0">Try searching with a different keyword or select another category.</p>
        </div>
    <?php endif; ?>
</div>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
