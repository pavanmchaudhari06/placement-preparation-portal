<?php
/**
 * Student Authentication and Session Management: student_auth.php
 * Strictly manages student login sessions via $_SESSION['student_id'].
 */

// Start session if not started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Idle timeout: log out after 30 minutes of inactivity
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
// Refresh activity timestamp
$_SESSION['last_activity'] = time();

/**
 * Check if a student is currently logged in
 */
function is_student_logged_in() {
    return isset($_SESSION['student_id']);
}

/**
 * Get current student user details
 */
function get_student_user() {
    if (is_student_logged_in()) {
        return [
            'id'    => $_SESSION['student_id'],
            'name'  => $_SESSION['student_name'] ?? 'Student',
            'email' => $_SESSION['student_email'] ?? ''
        ];
    }
    return null;
}

// Backward-compatible helper for existing templates
function get_logged_user() {
    return get_student_user();
}

/**
 * Restrict page access strictly to logged-in students
 */
function require_student() {
    if (!is_student_logged_in()) {
        // Redirect to student login
        $redirect = file_exists('login.php') ? 'login.php' : '../login.php';
        header("Location: " . $redirect);
        exit;
    }
}
?>
