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
$action = $_POST['action'] ?? '';

if (!in_array($action, ['enable', 'disable', 'resend'], true)) {
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("Invalid action."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email FROM admin_users WHERE id = :id");
    $stmt->execute([':id' => $targetId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("That admin no longer exists."));
        exit;
    }

    if ($action === 'disable') {
        // Never let an admin disable their own account -- there'd be no
        // one else to turn it back on if they're the only admin logged in
        // at the time.
        if ($targetId === (int) $_SESSION['admin_id']) {
            header("Location: settings.php?tab=admins&admin_error=" . urlencode("You can't disable your own account."));
            exit;
        }

        // logged_in = false alongside is_active = false so this takes
        // effect immediately, not just on their next login attempt --
        // app/require_admin.php checks is_active on every admin page load.
        $upd = $pdo->prepare("UPDATE admin_users SET is_active = false, logged_in = false WHERE id = :id");
        $upd->execute([':id' => $targetId]);

        header("Location: settings.php?tab=admins&admin_success=" . urlencode("{$target['name']}'s access has been disabled."));
        exit;
    }

    if ($action === 'enable') {
        $upd = $pdo->prepare("
            UPDATE admin_users
            SET is_active = true, failed_login_attempts = 0, unlock_token = NULL, unlock_token_expires = NULL
            WHERE id = :id
        ");
        $upd->execute([':id' => $targetId]);

        header("Location: settings.php?tab=admins&admin_success=" . urlencode("{$target['name']}'s access has been re-enabled."));
        exit;
    }

    if ($action === 'resend') {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 72 * 3600);

        $upd = $pdo->prepare("UPDATE admin_users SET unlock_token = :token, unlock_token_expires = :expires WHERE id = :id");
        $upd->execute([':token' => $token, ':expires' => $expires, ':id' => $targetId]);

        $sent = send_admin_invite_email($config, $target['email'], $target['name'], $token);

        if ($sent) {
            header("Location: settings.php?tab=admins&admin_success=" . urlencode("Invite resent to {$target['name']}."));
        } else {
            header("Location: settings.php?tab=admins&admin_error=" . urlencode("Couldn't resend the invite email. Please try again."));
        }
        exit;
    }

} catch (PDOException $e) {
    error_log("toggle_admin_user.php database error: " . $e->getMessage());
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("A database error occurred."));
    exit;
}
