<?php
/**
 * Administrator Logout: admin/logout.php
 * Clears administrator session and redirects to admin-login.php.
 */
session_start();

// Unset admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);

header("Location: admin-login.php?success=" . urlencode("Administrator logged out successfully."));
exit;
?>
