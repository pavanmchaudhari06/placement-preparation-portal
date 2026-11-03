<?php
/**
 * Student Company-wise Questions: company.php
 * Filter and practice questions specific to IT recruiters (TCS, Infosys, Wipro, Capgemini, Accenture).
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

// Fetch distinct company names from database
$comp_res = mysqli_query($conn, "SELECT DISTINCT company_name FROM company_questions ORDER BY company_name ASC");
$companies = [];
while ($row = mysqli_fetch_assoc($comp_res)) {
    $companies[] = $row['company_name'];
}

// Default selected company
$selected_company = trim($_GET['company'] ?? ($companies[0] ?? 'TCS'));
$safe_comp = mysqli_real_escape_string($conn, $selected_company);

// Fetch questions for selected company
$query = "SELECT * FROM company_questions WHERE company_name = '$safe_comp' ORDER BY id ASC";
$result = mysqli_query($conn, $query);

$page_title = $selected_company . " Interview Questions";
$page_heading = "Company-wise Placement Questions";
$current_page = "company";

require_once '../includes/student_header.php';
?>

<!-- Header Banner -->
<div class="card content-card border-0 bg-primary text-white p-4 mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-buildings-fill me-2"></i> Company-Specific Question Patterns</h4>
    <p class="text-white-50 mb-0">
        Leading recruiters follow specific exam patterns and question formats. Select a company below to prepare for its aptitude, technical, and HR interview rounds.
    </p>
</div>

<!-- Company Selection Badges / Tabs -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($companies as $comp): ?>
                <a href="company.php?company=<?php echo urlencode($comp); ?>" 
                   class="btn <?php echo ($selected_company === $comp) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white'; ?> px-4 py-2 fw-semibold">
                    <i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($comp); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Company Info Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold text-dark mb-0">
        Questions for <span class="text-primary"><?php echo htmlspecialchars($selected_company); ?></span>
    </h5>
    <span class="badge bg-secondary-subtle text-secondary">
        <?php echo mysqli_num_rows($result); ?> questions available
    </span>
</div>

<!-- Question Cards -->
<div class="row g-3">
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php $num = 0; while ($item = mysqli_fetch_assoc($result)): $num++; ?>
            <div class="col-12">
                <div class="card content-card shadow-sm border">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            Round: <?php echo htmlspecialchars($item['topic']); ?>
                        </span>
                        <span class="small text-muted">Q#<?php echo $num; ?></span>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-question-circle text-primary me-2"></i>
                            <?php echo nl2br(htmlspecialchars($item['question'])); ?>
                        </h6>
                        <div class="bg-light p-3 rounded border">
                            <div class="fw-bold text-secondary small mb-1">Answer / Recommended Solution:</div>
                            <div class="text-dark small" style="line-height: 1.7; white-space: pre-line;">
                                <?php echo htmlspecialchars($item['answer']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                <h5>No questions found for <?php echo htmlspecialchars($selected_company); ?> yet.</h5>
                <p>You can add questions via the Admin Panel!</p>
            </div>
        </div>
    <?php endif; ?>
</div>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
