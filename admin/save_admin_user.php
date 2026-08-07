<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/admin_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

$name = trim($_POST['admin_name'] ?? '');
$email = trim($_POST['admin_email'] ?? '');

if ($name === '' || $email === '') {
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("Name and email are both required."));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("That doesn't look like a valid email address."));
    exit;
}

// This new admin never receives or types a password chosen by whoever
// created the account -- a random, unguessable placeholder is stored (it
// can never successfully verify against anything) until they set their own
// via the emailed token link, which is the only thing that actually
// activates the account (is_active starts false).
$placeholderHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 72 * 3600); // 72 hours

try {
    $stmt = $pdo->prepare("
        INSERT INTO admin_users (name, email, password_hash, is_active, unlock_token, unlock_token_expires)
        VALUES (:name, :email, :password_hash, false, :token, :expires)
    ");
    $stmt->execute([
        ':name'          => $name,
        ':email'         => $email,
        ':password_hash' => $placeholderHash,
        ':token'         => $token,
        ':expires'       => $expires,
    ]);

    $sent = send_admin_invite_email($config, $email, $name, $token);

    if ($sent) {
        header("Location: settings.php?tab=admins&admin_success=" . urlencode("$name invited. They'll get an email to set their password."));
    } else {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("$name was added, but the invite email failed to send. Use \"Resend Invite\" on their row to try again."));
    }
    exit;

} catch (PDOException $e) {
    // Unique constraint on email is the most likely real-world cause here.
    if ($e->getCode() === '23505') {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("That email is already an admin."));
        exit;
    }
    error_log("save_admin_user.php database error: " . $e->getMessage());
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("A database error occurred adding that admin."));
    exit;
}
