<?php
/**
 * Admin Authentication and Session Management: admin_auth.php
 * Strictly manages administrator sessions via $_SESSION['admin_id'].
 */

// Start session if not started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Idle timeout for admin: 30 minutes inactivity
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: ../login.php'); // redirect to student login as per original behavior
    exit;
}
$_SESSION['last_activity'] = time();

/**
 * Check if an administrator is currently logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get current administrator user details
 */
function get_admin_user() {
    if (is_admin_logged_in()) {
        return [
            'id'    => $_SESSION['admin_id'],
            'name'  => $_SESSION['admin_name'] ?? 'Administrator',
            'email' => $_SESSION['admin_email'] ?? ''
        ];
    }
    return null;
}

// Backward-compatible helper for existing templates
function get_logged_user() {
    return get_admin_user();
}

/**
 * Restrict page access strictly to logged-in administrators.
 * If not logged in as admin (or if a student attempts to open an admin URL),
 * redirect silently to the student login page with NO "access denied" or "admin" message,
 * so external users cannot guess that an admin panel exists.
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        // Silently redirect to student login
        header("Location: ../login.php");
        exit;
    }
}
?>
