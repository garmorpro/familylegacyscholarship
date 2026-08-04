<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

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

try {
    $stmt = $pdo->prepare("
        INSERT INTO committee_members (name, email, created_at)
        VALUES (:name, :email, NOW())
    ");
    $stmt->execute([':name' => $name, ':email' => $email]);

    header("Location: settings.php?member_success=" . urlencode("$name added to the committee roster."));
    exit;

} catch (PDOException $e) {
    error_log("save_committee_member.php database error: " . $e->getMessage());
    header("Location: settings.php?member_error=" . urlencode("A database error occurred adding that member."));
    exit;
}
