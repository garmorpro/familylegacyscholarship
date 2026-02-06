<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/path.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . '/admin/auth/');
    exit;
}

// Optional: whitelist emails
$allowedAdmins = [
    'garrett.morgan.pro@gmail.com',
    'other.admin@example.com'
];

if (!in_array($_SESSION['admin_email'], $allowedAdmins)) {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/auth/?error=unauthorized');
    exit;
}

// Optional: check if user is actually marked as logged in in DB
$stmt = $pdo->prepare("
    SELECT logged_in FROM admin_users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $_SESSION['admin_id']]);
$loggedIn = $stmt->fetchColumn();

if (!$loggedIn) {
    // Session exists but DB says logged out
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/auth/?error=unauthorized');
    exit;
}
