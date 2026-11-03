<?php
/**
 * Student Registration Page - register.php
 * Implements server‑side validation, mysqli prepared statements, and
 * generic error handling. No technical DB errors are exposed.
 */
require_once 'includes/db.php';
require_once 'includes/validation.php';
require_once 'includes/student_auth.php';

// Redirect logged‑in students to their dashboard
if (is_student_logged_in()) {
    header('Location: student/dashboard.php');
    exit;
}

// Variables to hold error messages for each field
$name_err = $email_err = $pwd_err = $confirm_err = $phone_err = '';
$general_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and trim input
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ---------- Server‑side validation ----------
    $name_err  = validate_name($name);
    $email_err = validate_email($email, $conn);
    $pwd_err   = validate_password($password);
    $confirm_err = ($password !== $confirm_password) ? 'Passwords do not match.' : '';
    $phone_err = $phone ? validate_phone($phone) : '';

    // If all validations passed, attempt to insert the new user
    if (!$name_err && !$email_err && !$pwd_err && !$confirm_err && !$phone_err) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('ssss', $name, $email, $hash, $phone);
            if ($stmt->execute()) {
                // Registration successful – redirect to login with a friendly message
                header('Location: login.php?success=' . urlencode('Registration successful! Please log in.'));
                exit;
            } else {
                // Generic message – do not expose DB error
                $general_err = 'Something went wrong. Please try again later.';
            }
            $stmt->close();
        } else {
            $general_err = 'Something went wrong. Please try again later.';
        }
    } else {
        $general_err = 'Please correct the highlighted fields.';
    }
}

$page_title = 'Student Registration - Placement Preparation Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/student.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="text-center mb-4">
                <a href="index.php" class="text-decoration-none text-primary d-inline-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard-fill fs-2"></i>
                    <span class="fs-4 fw-bold">Placement Portal</span>
                </a>
                <p class="text-muted small mt-1">Create your student account</p>
            </div>
            <?php if (!empty($general_err)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($general_err); ?>
                </div>
            <?php endif; ?>
            <div class="card content-card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="register.php" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold small">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control <?= $name_err ? 'is-invalid' : '' ?>"
                                   pattern="[A-Za-z]+( [A-Za-z]+){0,29}" minlength="3" maxlength="30" required
                                   value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>">
                            <div class="invalid-feedback"><?= $name_err ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Student Email</label>
                            <input type="email" name="email" id="email" class="form-control <?= $email_err ? 'is-invalid' : '' ?>"
                                   maxlength="50" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div class="invalid-feedback"><?= $email_err ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold small">Phone (optional)</label>
                            <input type="text" name="phone" id="phone" class="form-control <?= $phone_err ? 'is-invalid' : '' ?>"
                                   pattern="\d{10}" maxlength="10" value="<?= htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            <div class="invalid-feedback"><?= $phone_err ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small">Password</label>
                            <input type="password" name="password" id="password" class="form-control <?= $pwd_err ? 'is-invalid' : '' ?>"
                                   minlength="8" required>
                            <div class="invalid-feedback"><?= $pwd_err ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold small">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control <?= $confirm_err ? 'is-invalid' : '' ?>"
                                   minlength="8" required>
                            <div class="invalid-feedback"><?= $confirm_err ?></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-person-check-fill me-1"></i> Register Student Account
                        </button>
                    </form>
                    <div class="text-center mt-3 pt-2 border-top">
                        <span class="small text-muted">Already have an account?</span>
                        <a href="login.php" class="small text-primary fw-semibold ms-1">Student login here</a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="index.php" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
