<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid member ID.");
}

$memberId = (int) $_POST['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM committee_members WHERE id = :id");
    $stmt->execute([':id' => $memberId]);

    header("Location: settings.php?member_success=" . urlencode("Committee member removed."));
    exit;

} catch (PDOException $e) {
    error_log("delete_committee_member.php database error: " . $e->getMessage());
    header("Location: settings.php?member_error=" . urlencode("A database error occurred removing that member."));
    exit;
}
