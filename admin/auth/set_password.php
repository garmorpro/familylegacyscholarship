<?php
require_once '../../app/db.php';
require_once '../../path.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';

if (!$token) {
    header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
    exit;
}

// Look up whoever this token belongs to -- shared with the lockout-unlock
// flow's token column, since both are "prove you own this email via a
// time-limited link" and never need to be valid at the same time for the
// same account.
$stmt = $pdo->prepare("SELECT id, name, unlock_token_expires FROM admin_users WHERE unlock_token = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
    exit;
}

if ($user['unlock_token_expires'] && strtotime($user['unlock_token_expires']) < time()) {
    header('Location: ' . BASE_URL . '/admin/auth/?error=expired');
    exit;
}

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("
            UPDATE admin_users
            SET password_hash = :hash, is_active = true, failed_login_attempts = 0,
                unlock_token = NULL, unlock_token_expires = NULL
            WHERE id = :id
        ");
        $upd->execute([':hash' => $hash, ':id' => $user['id']]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon-16.png">
    <link rel="apple-touch-icon" href="../../assets/images/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set Your Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #f6f7fb;
    --card: #ffffff;
    --primary: rgb(7,5,55);
    --primary-hover: rgb(20,16,80);
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
    width: 100%;
    text-align: center;
}
.card h1 { font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--primary); }
.card p { margin-bottom: 1.25rem; color: var(--muted); font-size: 14px; }
.field { text-align: left; margin-bottom: 14px; }
.field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--text); }
.field input {
    width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);
    font-family: inherit; font-size: 14px; box-sizing: border-box;
}
.error-box {
    background: rgba(220,53,69,0.08); color: #a3202c; border: 1px solid rgba(220,53,69,0.25);
    border-radius: 8px; padding: 10px 14px; font-size: 13.5px; margin-bottom: 16px; text-align: left;
}
button {
    width: 100%; padding: 0.7rem 1.25rem; border: none; border-radius: 10px;
    background: var(--primary); color: #fff; text-decoration: none; font-weight: 600;
    font-size: 14.5px; cursor: pointer; transition: background 0.15s ease;
}
button:hover { background: var(--primary-hover); }
.card a.btn-link {
    display: inline-block; padding: 0.65rem 1.25rem; border-radius: 10px;
    background: var(--primary); color: #fff; text-decoration: none; font-weight: 500;
}
</style>
</head>
<body>
<div class="card">
<?php if ($success): ?>
    <h1>Password Set</h1>
    <p>Your password has been set. You can now log in to the admin portal.</p>
    <a class="btn-link" href="<?= BASE_URL ?>/admin/auth/">Go to Login</a>
<?php else: ?>
    <h1>Set Your Password</h1>
    <p>Welcome<?= !empty($user['name']) ? ', ' . htmlspecialchars($user['name']) : '' ?> &mdash; choose a password for your admin account.</p>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <div class="field">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" minlength="8" required autofocus>
        </div>
        <div class="field">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
        </div>
        <button type="submit">Set Password</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
