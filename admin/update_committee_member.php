<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

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
    $stmt = $pdo->prepare("UPDATE committee_members SET name = :name, email = :email WHERE id = :id");
    $stmt->execute([':name' => $name, ':email' => $email, ':id' => $targetId]);

    if ($stmt->rowCount() === 0) {
        header("Location: settings.php?tab=committee&member_error=" . urlencode("That committee member no longer exists."));
        exit;
    }

    header("Location: settings.php?tab=committee&member_success=" . urlencode("$name updated."));
    exit;

} catch (PDOException $e) {
    error_log("update_committee_member.php database error: " . $e->getMessage());
    header("Location: settings.php?tab=committee&member_error=" . urlencode("A database error occurred updating that member."));
    exit;
}
