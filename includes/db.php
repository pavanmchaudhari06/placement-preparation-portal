<?php
/**
 * Database Connection File: db.php
 * Connects the PHP application to the MySQL database in XAMPP.
 */

// Database credentials for default XAMPP setup
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';          // Default XAMPP password is empty
$db_name = 'placement_portal';

// Establish connection using mysqli
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check if connection was successful
if (!$conn) {
    die("<div style='font-family:sans-serif; padding:20px; color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; border-radius:5px;'>
        <h3>Database Connection Error!</h3>
        <p>Could not connect to MySQL database <strong>{$db_name}</strong>.</p>
        <p><strong>Reason:</strong> " . mysqli_connect_error() . "</p>
        <p>Please make sure <strong>Apache and MySQL</strong> are running in your XAMPP Control Panel and you have imported <code>database.sql</code> into phpMyAdmin.</p>
    </div>");
}

// Set UTF-8 encoding for standard character support
mysqli_set_charset($conn, "utf8mb4");
?>
