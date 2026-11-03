<?php
/**
 * Private Administrator Login: admin-login.php
 * Completely isolated from the student portal.
 */
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

// If already authenticated as admin, redirect to admin dashboard
if (is_admin_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';

// Process Admin Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both administrator email and password.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $query = "SELECT * FROM admins WHERE email = '$safe_email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);

            if (password_verify($password, $admin['password']) || $password === $admin['password']) {
                // Set dedicated admin session variables
                $_SESSION['admin_id']    = $admin['id'];
                $_SESSION['admin_name']  = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error_msg = "Invalid administrator credentials.";
            }
        } else {
            $error_msg = "Administrator account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Login - Portal Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 420px;
            padding: 32px;
        }
        .form-control {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #ffffff;
        }
        .form-control:focus {
            background-color: #0f172a;
            border-color: #10b981;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
        }
        .input-group-text {
            background-color: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="admin-login-card">
    <!-- Branding -->
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-2">
            <i class="bi bi-shield-lock-fill fs-2"></i>
        </div>
        <h4 class="fw-bold text-white mb-1">Administrator Portal</h4>
        <p class="text-secondary small mb-0">Authorized personnel only</p>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger py-2 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success py-2 small" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="admin-login.php">
        <div class="mb-3">
            <label for="email" class="form-label text-light small fw-semibold">Admin Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" class="form-control" required placeholder="admin@portal.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label text-light small fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>
        </div>

        <button type="submit" class="btn btn-admin w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Authenticate as Admin
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
