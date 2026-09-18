<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/committee_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    die("Invalid member ID.");
}

$memberId = (int) $_POST['id'];

try {
    $stmt = $pdo->prepare("SELECT id, name, email, confirmed_at FROM committee_members WHERE id = :id");
    $stmt->execute([':id' => $memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        header("Location: settings.php?member_error=" . urlencode("That committee member no longer exists."));
        exit;
    }

    if (!empty($member['confirmed_at'])) {
        header("Location: settings.php?member_error=" . urlencode("{$member['name']} has already confirmed -- there's nothing to resend."));
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $upd = $pdo->prepare("UPDATE committee_members SET confirmation_token = :token WHERE id = :id");
    $upd->execute([':token' => $token, ':id' => $memberId]);

    $sent = send_committee_confirmation_email($config, $member['email'], $member['name'], $token);

    if ($sent) {
        header("Location: settings.php?member_success=" . urlencode("Confirmation email resent to {$member['name']}."));
    } else {
        header("Location: settings.php?member_error=" . urlencode("Couldn't resend the confirmation email. Please try again."));
    }
    exit;

} catch (PDOException $e) {
    error_log("resend_committee_confirmation.php database error: " . $e->getMessage());
    header("Location: settings.php?member_error=" . urlencode("A database error occurred."));
    exit;
}
