<?php
// ============================================
// Database connection (XAMPP default settings)
// Change DB_PASS below if your MySQL root user has a password set
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lms_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . " — Make sure XAMPP MySQL is running and you imported sql/lms.sql in phpMyAdmin.");
}
$conn->set_charset("utf8mb4");
