<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/admin_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    die("Invalid admin ID.");
}

$targetId = (int) $_POST['id'];
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

try {
    $stmt = $pdo->prepare("SELECT email, unlock_token, is_active, failed_login_attempts FROM admin_users WHERE id = :id");
    $stmt->execute([':id' => $targetId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("That admin no longer exists."));
        exit;
    }

    $upd = $pdo->prepare("UPDATE admin_users SET name = :name, email = :email WHERE id = :id");
    $upd->execute([':name' => $name, ':email' => $email, ':id' => $targetId]);

    // If their invite was still pending and the email just changed, the
    // original invite is now unreachable -- the person it was meant for
    // can never see it at the old address. Send a fresh one to wherever
    // they actually are now, rather than leaving a dead-end account.
    $wasPending = !empty($existing['unlock_token']) && (int) $existing['failed_login_attempts'] === 0 && !$existing['is_active'];
    $emailChanged = strcasecmp($existing['email'], $email) !== 0;

    if ($wasPending && $emailChanged) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 72 * 3600);
        $tokenUpd = $pdo->prepare("UPDATE admin_users SET unlock_token = :token, unlock_token_expires = :expires WHERE id = :id");
        $tokenUpd->execute([':token' => $token, ':expires' => $expires, ':id' => $targetId]);
        send_admin_invite_email($config, $email, $name, $token);

        header("Location: settings.php?tab=admins&admin_success=" . urlencode("$name updated -- a new invite was sent to their updated email."));
        exit;
    }

    header("Location: settings.php?tab=admins&admin_success=" . urlencode("$name updated."));
    exit;

} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("That email is already used by another admin."));
        exit;
    }
    error_log("update_admin_user.php database error: " . $e->getMessage());
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("A database error occurred updating that admin."));
    exit;
}
