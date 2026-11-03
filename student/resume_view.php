<?php
/**
 * ATS-Friendly Resume Preview & Print View: resume_view.php
 * Generates an ATS-compliant resume with 1-click browser printing/PDF download.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$user = get_logged_user();
$student_id = $user['id'];

// Fetch student's resume
$query = "SELECT * FROM resumes WHERE user_id = $student_id LIMIT 1";
$result = mysqli_query($conn, $query);
$resume = mysqli_fetch_assoc($result);

$page_title = "ATS Resume View";
$page_heading = "ATS Resume Generator";
$current_page = "resume";

require_once '../includes/student_header.php';
?>

<!-- Action Bar (Hidden when printing) -->
<div class="no-print mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white p-3 rounded border shadow-sm">
    <div>
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-file-earmark-check-fill text-success me-2"></i> ATS Resume Preview
        </h5>
        <span class="text-muted small">Optimized single-column format for Applicant Tracking Systems (ATS)</span>
    </div>
    <div class="d-flex gap-2">
        <a href="resume.php" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit Details
        </a>
        <button onclick="window.print()" class="btn btn-primary fw-bold shadow-sm">
            <i class="bi bi-printer-fill me-1"></i> Print / Download as PDF
        </button>
    </div>
</div>

<?php if (!$resume): ?>
    <div class="card p-5 text-center text-muted">
        <i class="bi bi-file-earmark-x fs-1 mb-2 text-warning"></i>
        <h5>No Resume Data Found</h5>
        <p>You haven't filled in your resume details yet.</p>
        <div>
            <a href="resume.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Your Resume Now
            </a>
        </div>
    </div>
<?php else: ?>

    <!-- ATS Printable Resume Container -->
    <div class="resume-container">
        
        <!-- Header: Name & Contact Details -->
        <header class="resume-header">
            <h1 class="resume-name"><?php echo htmlspecialchars($resume['full_name']); ?></h1>
            <div class="resume-contacts">
                <?php if (!empty($resume['email'])): ?>
                    <span><i class="bi bi-envelope me-1 no-print"></i> <?php echo htmlspecialchars($resume['email']); ?></span>
                <?php endif; ?>
                <?php if (!empty($resume['phone'])): ?>
                    <span>&bull;</span>
                    <span><i class="bi bi-telephone me-1 no-print"></i> <?php echo htmlspecialchars($resume['phone']); ?></span>
                <?php endif; ?>
                <?php if (!empty($resume['linkedin'])): ?>
                    <span>&bull;</span>
                    <span><i class="bi bi-linkedin me-1 no-print"></i> <?php echo htmlspecialchars($resume['linkedin']); ?></span>
                <?php endif; ?>
                <?php if (!empty($resume['github'])): ?>
                    <span>&bull;</span>
                    <span><i class="bi bi-github me-1 no-print"></i> <?php echo htmlspecialchars($resume['github']); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Professional Summary -->
        <?php if (!empty($resume['summary'])): ?>
            <section class="mb-4">
                <h2 class="resume-section-title">Professional Summary</h2>
                <p style="text-align: justify; margin-bottom: 0;">
                    <?php echo nl2br(htmlspecialchars($resume['summary'])); ?>
                </p>
            </section>
        <?php endif; ?>

        <!-- Education Background -->
        <?php if (!empty($resume['education'])): ?>
            <section class="mb-4">
                <h2 class="resume-section-title">Education</h2>
                <div style="white-space: pre-line; line-height: 1.6;">
                    <?php echo htmlspecialchars($resume['education']); ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Technical Skills -->
        <?php if (!empty($resume['skills'])): ?>
            <section class="mb-4">
                <h2 class="resume-section-title">Technical Skills</h2>
                <div style="white-space: pre-line; line-height: 1.6;">
                    <?php echo htmlspecialchars($resume['skills']); ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Key Projects -->
        <?php if (!empty($resume['projects'])): ?>
            <section class="mb-4">
                <h2 class="resume-section-title">Key Projects</h2>
                <div style="white-space: pre-line; line-height: 1.6;">
                    <?php echo htmlspecialchars($resume['projects']); ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Experience / Internships -->
        <?php if (!empty($resume['experience'])): ?>
            <section class="mb-4">
                <h2 class="resume-section-title">Experience & Internships</h2>
                <div style="white-space: pre-line; line-height: 1.6;">
                    <?php echo htmlspecialchars($resume['experience']); ?>
                </div>
            </section>
        <?php endif; ?>

    </div>

<?php endif; ?>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
