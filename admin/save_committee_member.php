<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/committee_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

$name = trim($_POST['member_name'] ?? '');
$email = trim($_POST['member_email'] ?? '');

if ($name === '' || $email === '') {
    header("Location: settings.php?member_error=" . urlencode("Name and email are both required."));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: settings.php?member_error=" . urlencode("That doesn't look like a valid email address."));
    exit;
}

// A new member starts unconfirmed -- confirmed_at only ever gets set by
// them clicking through the emailed link (committee/confirm.php), never
// by anything an admin does here. Final Review can't be sent to them
// until then (see admin/send_committee_review.php).
$token = bin2hex(random_bytes(32));

try {
    $stmt = $pdo->prepare("
        INSERT INTO committee_members (name, email, confirmation_token, created_at)
        VALUES (:name, :email, :token, NOW())
    ");
    $stmt->execute([':name' => $name, ':email' => $email, ':token' => $token]);

    $sent = send_committee_confirmation_email($config, $email, $name, $token);

    if ($sent) {
        header("Location: settings.php?member_success=" . urlencode("$name added. They'll get an email to confirm before Final Review can be sent to them."));
    } else {
        header("Location: settings.php?member_error=" . urlencode("$name was added, but the confirmation email failed to send. Use \"Resend confirmation\" on their row to try again."));
    }
    exit;

} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        header("Location: settings.php?member_error=" . urlencode("That email is already on the committee roster."));
        exit;
    }
    error_log("save_committee_member.php database error: " . $e->getMessage());
    header("Location: settings.php?member_error=" . urlencode("A database error occurred adding that member."));
    exit;
}
