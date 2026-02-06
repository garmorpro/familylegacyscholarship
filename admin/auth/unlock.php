<?php
session_start();
require_once '../../app/db.php';
require_once '../../path.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die('Invalid link.');
}

// Lookup user
$stmt = $pdo->prepare("
    SELECT id, unlock_token_expires
    FROM admin_users
    WHERE unlock_token = :token
    LIMIT 1
");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Invalid or expired token.');
}

// Check expiry
if ($user['unlock_token_expires'] && strtotime($user['unlock_token_expires']) < time()) {
    die('Token has expired.');
}

// Unlock the account
$stmt = $pdo->prepare("
    UPDATE admin_users
    SET is_active = true,
        failed_login_attempts = 0,
        unlock_token = NULL,
        unlock_token_expires = NULL
    WHERE id = :id
");
$stmt->execute(['id' => $user['id']]);

echo "Your account has been unlocked! You can now <a href='login.php'>login</a>.";
