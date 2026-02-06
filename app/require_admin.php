<?php
session_start();
require_once 'db.php';
require_once '../path.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    // Redirect to login page
    header('Location: ' . BASE_URL . '/admin/auth/login.php');
    exit;
}

// Optional: whitelist emails for extra safety
$allowedAdmins = [
    'garrett.morgan.pro@gmail.com',
    'other.admin@example.com'
];

if (!in_array($_SESSION['admin_email'], $allowedAdmins)) {
    // Invalid session email — log out
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/auth/login.php?error=unauthorized');
    exit;
}
