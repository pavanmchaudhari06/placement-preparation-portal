<?php
/**
 * Student Logout: logout.php
 * Clears student session and redirects to student login.
 */
session_start();

// Unset student session variables
unset($_SESSION['student_id']);
unset($_SESSION['student_name']);
unset($_SESSION['student_email']);

header("Location: login.php?success=" . urlencode("You have logged out successfully."));
exit;
?>
