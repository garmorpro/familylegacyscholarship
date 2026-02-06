<?php
session_start();
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/path.php';

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Optionally, log the attempt here
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. You do not have permission to view this page.');
}

// Optionally, check for specific emails (whitelist)
$allowedAdmins = [
    'garrett.morgan.pro@gmail.com',
    'other.admin@example.com'
];

if (!in_array($_SESSION['admin_email'], $allowedAdmins)) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. Your account is not authorized.');
}
