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
    die("Invalid committee member ID.");
}

$targetId = (int) $_POST['id'];
$name = trim($_POST['member_name'] ?? '');
$email = trim($_POST['member_email'] ?? '');

if ($name === '' || $email === '') {
    header("Location: settings.php?tab=committee&member_error=" . urlencode("Name and email are both required."));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: settings.php?tab=committee&member_error=" . urlencode("That doesn't look like a valid email address."));
    exit;
}

try {
    $current = $pdo->prepare("SELECT email, confirmed_at FROM committee_members WHERE id = :id");
    $current->execute([':id' => $targetId]);
    $existing = $current->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        header("Location: settings.php?tab=committee&member_error=" . urlencode("That committee member no longer exists."));
        exit;
    }

    // Changing the email address means it hasn't been verified -- a
    // previously-confirmed member goes back to unconfirmed and gets a
    // fresh confirmation link, same as a brand-new member would.
    $emailChanged = strcasecmp($existing['email'], $email) !== 0;

    if ($emailChanged) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("
            UPDATE committee_members
            SET name = :name, email = :email, confirmed_at = NULL, confirmation_token = :token
            WHERE id = :id
        ");
        $stmt->execute([':name' => $name, ':email' => $email, ':token' => $token, ':id' => $targetId]);

        $sent = send_committee_confirmation_email($config, $email, $name, $token);
        $message = $sent
            ? "$name updated. Since their email changed, they'll need to confirm it again before Final Review can be sent to them."
            : "$name was updated, but the confirmation email failed to send. Use \"Resend confirmation\" on their row to try again.";

        header("Location: settings.php?tab=committee&member_success=" . urlencode($message));
        exit;
    }

    $stmt = $pdo->prepare("UPDATE committee_members SET name = :name, email = :email WHERE id = :id");
    $stmt->execute([':name' => $name, ':email' => $email, ':id' => $targetId]);

    header("Location: settings.php?tab=committee&member_success=" . urlencode("$name updated."));
    exit;

} catch (PDOException $e) {
    error_log("update_committee_member.php database error: " . $e->getMessage());
    header("Location: settings.php?tab=committee&member_error=" . urlencode("A database error occurred updating that member."));
    exit;
}
