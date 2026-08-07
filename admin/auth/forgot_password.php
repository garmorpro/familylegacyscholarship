<?php
session_start();
require_once '../../app/db.php';
require_once '../../app/csrf.php';
require_once '../../app/admin_mailer.php';

$submitted = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Security check failed. Please refresh the page and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, is_active, failed_login_attempts, unlock_token FROM admin_users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                // Eligible unless the account was deliberately disabled by
                // another admin (is_active=false, not from a failed-login
                // lockout, and not a still-pending invite) -- that state
                // must never be something the account holder can undo on
                // their own, or "disable" would mean nothing. A locked-out
                // account (too many failed attempts) IS eligible: this is
                // the actual fix for there being no real way to set a new
                // password before, only re-enable the old one.
                $isDisabledByAdmin = $admin
                    && !$admin['is_active']
                    && (int) $admin['failed_login_attempts'] < 3
                    && empty($admin['unlock_token']);

                if ($admin && !$isDisabledByAdmin) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour -- shorter than an invite, since this is a live credential recovery

                    $upd = $pdo->prepare("UPDATE admin_users SET unlock_token = :token, unlock_token_expires = :expires WHERE id = :id");
                    $upd->execute([':token' => $token, ':expires' => $expires, ':id' => $admin['id']]);

                    $name = !empty($admin['name']) ? $admin['name'] : $admin['email'];
                    send_admin_password_reset_email($config, $admin['email'], $name, $token);
                }

                // Same response whether or not the email matched an account,
                // or was eligible -- never confirms or denies which emails
                // are registered admins.
                $submitted = true;
            } catch (PDOException $e) {
                error_log("forgot_password.php database error: " . $e->getMessage());
                $error = 'Something went wrong. Please try again.';
            }
        }
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
<title>Forgot Password</title>

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
* { box-sizing: border-box; }
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
.login-card {
  width: 100%;
  max-width: 380px;
  background: var(--card);
  padding: 2.25rem;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,.08);
}
.login-card h1 { margin: 0 0 .5rem; font-size: 1.6rem; font-weight: 600; }
.login-card p { margin: 0 0 1.75rem; color: var(--muted); font-size: .95rem; }
.form-group { margin-bottom: 1.25rem; }
label { display: block; margin-bottom: .35rem; font-size: .85rem; font-weight: 500; }
input {
  width: 100%;
  padding: .65rem .75rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  font-size: .95rem;
  font-family: inherit;
}
input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(7,5,55,.15);
}
button {
  width: 100%;
  padding: .7rem;
  border-radius: 10px;
  border: none;
  font-size: .95rem;
  font-weight: 500;
  background: var(--primary);
  color: #fff;
  cursor: pointer;
  transition: background .15s ease;
}
button:hover { background: var(--primary-hover); }
.footer-text { margin-top: 1.5rem; text-align: center; font-size: .8rem; color: var(--muted); }
.footer-text a { color: var(--muted); }
.error-message {
  margin-bottom: 1rem;
  padding: .6rem .75rem;
  border-radius: 8px;
  font-size: .85rem;
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}
.success-message {
  padding: .8rem .9rem;
  border-radius: 8px;
  font-size: .9rem;
  background: rgba(25,135,84,0.1);
  color: #146c43;
  border: 1px solid rgba(25,135,84,0.25);
  line-height: 1.5;
}
</style>
</head>
<body>

<div class="login-card">
<?php if ($submitted): ?>
    <h1>Check Your Email</h1>
    <div class="success-message">
        If that email belongs to an active admin account, a password reset link is on its way. It's valid for 1 hour.
    </div>
    <div class="footer-text">
        <a href="index.php">Back to login</a>
    </div>
<?php else: ?>
    <h1>Forgot Password</h1>
    <p>Enter your admin email and we'll send you a link to reset your password.</p>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit">Send Reset Link</button>
    </form>

    <div class="footer-text">
        <a href="index.php">Back to login</a>
    </div>
<?php endif; ?>
</div>

</body>
</html>
