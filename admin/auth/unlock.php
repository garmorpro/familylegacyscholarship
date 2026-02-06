<?php
session_start();
require_once '../../app/db.php';
require_once '../../path.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
    exit;
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
    header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
    exit;
}

// Check expiry
if ($user['unlock_token_expires'] && strtotime($user['unlock_token_expires']) < time()) {
    header('Location: ' . BASE_URL . '/admin/auth/?error=expired');
    exit;
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

// ✅ Success message HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Unlocked</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #f6f7fb;
    --card: #ffffff;
    --primary: #2563eb;
    --primary-hover: #1e40af;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e5e7eb;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Inter', system-ui, sans-serif;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text);
}

.card {
    background: var(--card);
    padding: 2.25rem;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,.08);
    max-width: 400px;
    text-align: center;
}

.card h1 {
    font-size: 1.8rem;
    margin-bottom: 1rem;
    color: var(--primary);
}

.card p {
    margin-bottom: 1.5rem;
    color: var(--text);
}

.card a {
    display: inline-block;
    padding: 0.65rem 1.25rem;
    border-radius: 10px;
    background: var(--primary);
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.15s ease;
}

.card a:hover {
    background: var(--primary-hover);
}
</style>
</head>
<body>
<div class="card">
    <h1>Account Unlocked</h1>
    <p>Your admin account has been successfully unlocked! You can now access the portal.</p>
    <a href="<?= BASE_URL ?>/admin/auth/">Go to Login</a>
</div>
</body>
</html>
