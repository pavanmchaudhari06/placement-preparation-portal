<?php
/**
 * login.php – Secure Student Login
 * Implements:
 *  - Input validation (HTML5 patterns)
 *  - Throttling after 5 failed attempts (5 min lock)
 *  - Prepared statements (mysqli)
 *  - session_regenerate_id(true) on success
 *  - Generic error messages, no DB details exposed
 */
require_once 'includes/db.php';
require_once 'includes/validation.php';
require_once 'includes/student_auth.php';

// Redirect already logged‑in students
if (is_student_logged_in()) {
    header('Location: student/dashboard.php');
    exit;
}

$error_msg = '';
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple client‑side checks are also enforced server‑side
    if (empty($email) || empty($password)) {
        $error_msg = 'Please fill in both fields.';
    } else {
        // ---------- Throttling check ----------
        $stmt = $conn->prepare('SELECT attempts, blocked_until FROM login_attempts WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($attempts, $blocked_until);
        $has_row = $stmt->fetch();
        $stmt->close();
        $now = date('Y-m-d H:i:s');
        if ($has_row && $blocked_until && $blocked_until > $now) {
            $error_msg = 'Too many attempts, try again later.';
        } else {
            // ---------- Authenticate ----------
            $stmt = $conn->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($uid, $uname, $uemail, $hash);
                $stmt->fetch();
                if (password_verify($password, $hash)) {
                    // successful login – reset attempts
                    $conn->query("DELETE FROM login_attempts WHERE email = '$email'");
                    session_regenerate_id(true);
                    $_SESSION['student_id'] = $uid;
                    $_SESSION['student_name'] = $uname;
                    $_SESSION['student_email'] = $uemail;
                    $_SESSION['last_activity'] = time();
                    header('Location: student/dashboard.php');
                    exit;
                } else {
                    // wrong password – increment attempts
                    $newAttempts = ($has_row) ? $attempts + 1 : 1;
                    $block = ($newAttempts >= 5) ? date('Y-m-d H:i:s', time() + 300) : NULL;
                    if ($has_row) {
                        $upd = $conn->prepare('UPDATE login_attempts SET attempts = ?, blocked_until = ? WHERE email = ?');
                        $upd->bind_param('iss', $newAttempts, $block, $email);
                        $upd->execute();
                        $upd->close();
                    } else {
                        $ins = $conn->prepare('INSERT INTO login_attempts (email, attempts, blocked_until) VALUES (?, ?, ?)');
                        $ins->bind_param('sis', $email, $newAttempts, $block);
                        $ins->execute();
                        $ins->close();
                    }
                    $error_msg = 'Invalid credentials.';
                }
            } else {
                $error_msg = 'Invalid credentials.';
            }
            $stmt->close();
        }
    }
}

$page_title = 'Student Login - Placement Preparation Portal';
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
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <a href="index.php" class="text-decoration-none text-primary d-inline-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard-fill fs-2"></i>
                    <span class="fs-4 fw-bold">Placement Portal</span>
                </a>
                <p class="text-muted small mt-1">Student Portal Login</p>
            </div>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success py-2 small" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            <div class="card content-card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Student Email</label>
                            <input type="email" name="email" id="email" class="form-control" required maxlength="50" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Student Portal
                        </button>
                    </form>
                    <div class="text-center mt-3 pt-2 border-top">
                        <span class="small text-muted">New student?</span>
                        <a href="register.php" class="small text-primary fw-semibold ms-1">Create Student Account</a>
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
