<?php
session_start();
require_once 'db.php';
require_once '../path.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ' . BASE_URL . '/admin/auth/');
    exit;
}

// The DB row is the single source of truth for who's allowed in -- no
// hardcoded email list here. Checking is_active on every page load (not
// just at login) is what makes disabling an account take effect
// immediately, even for someone already mid-session, instead of only
// blocking their *next* login attempt.
$stmt = $pdo->prepare("
    SELECT logged_in, is_active FROM admin_users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $_SESSION['admin_id']]);
$adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminRow || !$adminRow['logged_in'] || !$adminRow['is_active']) {
    // Session exists but the account is logged out, disabled, or gone
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/auth/?error=unauthorized');
    exit;
}
