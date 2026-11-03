<?php
/**
 * Student Resume Builder: resume.php
 * Form to input personal details, education, skills, projects, and work experience.
 */
require_once '../includes/db.php';
require_once '../includes/student_auth.php';

require_student();

$user = get_logged_user();
$student_id = $user['id'];

$message = '';
$error = '';

// Check if resume already exists for this student
$fetch_query = "SELECT * FROM resumes WHERE user_id = $student_id LIMIT 1";
$fetch_res = mysqli_query($conn, $fetch_query);
$resume = mysqli_fetch_assoc($fetch_res) ?? [];

// Default name and email from users table if not set
$full_name  = $resume['full_name'] ?? $user['name'];
$email      = $resume['email'] ?? $user['email'];
$phone      = $resume['phone'] ?? '';
$linkedin   = $resume['linkedin'] ?? '';
$github     = $resume['github'] ?? '';
$summary    = $resume['summary'] ?? '';
$education  = $resume['education'] ?? '';
$skills     = $resume['skills'] ?? '';
$projects   = $resume['projects'] ?? '';
$experience = $resume['experience'] ?? '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $linkedin   = trim($_POST['linkedin'] ?? '');
    $github     = trim($_POST['github'] ?? '');
    $summary    = trim($_POST['summary'] ?? '');
    $education  = trim($_POST['education'] ?? '');
    $skills     = trim($_POST['skills'] ?? '');
    $projects   = trim($_POST['projects'] ?? '');
    $experience = trim($_POST['experience'] ?? '');

    if (empty($full_name) || empty($email)) {
        $error = "Full Name and Email are required.";
    } else {
        $safe_name       = mysqli_real_escape_string($conn, $full_name);
        $safe_email      = mysqli_real_escape_string($conn, $email);
        $safe_phone      = mysqli_real_escape_string($conn, $phone);
        $safe_linkedin   = mysqli_real_escape_string($conn, $linkedin);
        $safe_github     = mysqli_real_escape_string($conn, $github);
        $safe_summary    = mysqli_real_escape_string($conn, $summary);
        $safe_education  = mysqli_real_escape_string($conn, $education);
        $safe_skills     = mysqli_real_escape_string($conn, $skills);
        $safe_projects   = mysqli_real_escape_string($conn, $projects);
        $safe_experience = mysqli_real_escape_string($conn, $experience);

        // Insert or Update resume record
        $upsert_query = "INSERT INTO resumes (user_id, full_name, email, phone, linkedin, github, summary, education, skills, projects, experience)
                         VALUES ($student_id, '$safe_name', '$safe_email', '$safe_phone', '$safe_linkedin', '$safe_github', '$safe_summary', '$safe_education', '$safe_skills', '$safe_projects', '$safe_experience')
                         ON DUPLICATE KEY UPDATE 
                         full_name = '$safe_name',
                         email = '$safe_email',
                         phone = '$safe_phone',
                         linkedin = '$safe_linkedin',
                         github = '$safe_github',
                         summary = '$safe_summary',
                         education = '$safe_education',
                         skills = '$safe_skills',
                         projects = '$safe_projects',
                         experience = '$safe_experience'";

        if (mysqli_query($conn, $upsert_query)) {
            $message = "Resume saved successfully! You can now view and download your ATS-friendly format.";
        } else {
            $error = "Failed to save resume: " . mysqli_error($conn);
        }
    }
}

$page_title = "Resume Builder";
$page_heading = "ATS Resume Builder";
$current_page = "resume";

require_once '../includes/student_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card content-card border-0 bg-primary text-white p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-person-fill me-2"></i> ATS-Compliant Resume Builder</h4>
                    <p class="text-white-50 mb-0">
                        Fill in your academic and professional details below. The system automatically structures your data into an ATS-friendly format that passes automated HR resume screening tools.
                    </p>
                </div>
                <div>
                    <a href="resume_view.php" class="btn btn-light text-primary fw-bold shadow-sm">
                        <i class="bi bi-eye-fill me-1"></i> View & Print Resume
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($message); ?>
        <a href="resume_view.php" class="btn btn-sm btn-success ms-2 fw-semibold">Preview ATS Resume Now</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card content-card shadow-sm">
    <div class="card-header fw-bold text-dark">
        <i class="bi bi-pencil-square me-1"></i> Resume Details Form
    </div>
    <div class="card-body p-4">
        <form method="POST" action="resume.php">
            
            <!-- Personal Contact Details -->
            <h5 class="fw-bold text-primary mb-3">1. Personal & Contact Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($full_name); ?>" placeholder="e.g. Rahul Sharma">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email Address *</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>" placeholder="e.g. rahul@example.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+91 9876543210">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">LinkedIn Profile URL</label>
                    <input type="text" name="linkedin" class="form-control" value="<?php echo htmlspecialchars($linkedin); ?>" placeholder="linkedin.com/in/username">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">GitHub Profile URL</label>
                    <input type="text" name="github" class="form-control" value="<?php echo htmlspecialchars($github); ?>" placeholder="github.com/username">
                </div>
            </div>

            <hr class="my-4">

            <!-- Professional Summary -->
            <h5 class="fw-bold text-primary mb-3">2. Career Objective / Summary</h5>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Short Profile Summary (2-4 lines)</label>
                <textarea name="summary" class="form-control" rows="3" placeholder="Enthusiastic Computer Science undergraduate with strong foundation in data structures, algorithms, and web development..."><?php echo htmlspecialchars($summary); ?></textarea>
            </div>

            <hr class="my-4">

            <!-- Education -->
            <h5 class="fw-bold text-primary mb-3">3. Education Background</h5>
            <div class="mb-4">
                <label class="form-label small fw-semibold">College / High School (Degree, Institute, Year, CGPA / %)</label>
                <textarea name="education" class="form-control" rows="4" placeholder="B.Tech in Computer Science and Engineering&#10;XYZ Institute of Technology, 2021 - 2025 | CGPA: 8.5/10.0&#10;&#10;Class XII (CBSE Board)&#10;ABC Senior Secondary School, 2021 | 90.2%"><?php echo htmlspecialchars($education); ?></textarea>
                <div class="form-text small">Enter each degree or institution on a new line.</div>
            </div>

            <hr class="my-4">

            <!-- Skills -->
            <h5 class="fw-bold text-primary mb-3">4. Technical & Core Skills</h5>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Skills (Categorized or comma separated)</label>
                <textarea name="skills" class="form-control" rows="3" placeholder="Programming Languages: C, C++, Java, Python, PHP, JavaScript&#10;Web Technologies: HTML5, CSS3, Bootstrap 5, MySQL&#10;Tools & Platforms: Git, GitHub, VS Code, XAMPP&#10;Core Concepts: Data Structures, OOPs, DBMS, Operating Systems"><?php echo htmlspecialchars($skills); ?></textarea>
            </div>

            <hr class="my-4">

            <!-- Academic Projects -->
            <h5 class="fw-bold text-primary mb-3">5. Projects</h5>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Academic / Personal Projects (Title, Tech Stack & Key Bullet Points)</label>
                <textarea name="projects" class="form-control" rows="5" placeholder="1. Placement Preparation Portal&#10;- Built a comprehensive placement readiness application using PHP, MySQL, and Bootstrap 5.&#10;- Implemented timed tests, weak-topic recommendation engine, and ATS resume generator.&#10;&#10;2. Online Examination System&#10;- Created an interactive exam portal with automated evaluation."><?php echo htmlspecialchars($projects); ?></textarea>
            </div>

            <hr class="my-4">

            <!-- Experience / Internships -->
            <h5 class="fw-bold text-primary mb-3">6. Internships / Experience & Extra-curriculars</h5>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Work Experience / Internships / Certifications</label>
                <textarea name="experience" class="form-control" rows="4" placeholder="Software Engineering Intern | ABC Tech Solutions (June 2024 - Aug 2024)&#10;- Assisted in developing database schemas and responsive UI templates.&#10;- Participated in weekly code reviews and unit testing."><?php echo htmlspecialchars($experience); ?></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                    <i class="bi bi-save-fill me-1"></i> Save Resume Details
                </button>
                <a href="resume_view.php" class="btn btn-outline-success btn-lg px-4 fw-semibold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> View & Print ATS Resume
                </a>
            </div>

        </form>
    </div>
</div>

</main>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
