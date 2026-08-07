<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    die("Invalid admin ID.");
}

$targetId = (int) $_POST['id'];

// Can't happen through the UI (disabling your own account is already
// blocked, and only a disabled account can be deleted) but guarded here
// too as a server-side backstop.
if ($targetId === (int) ($_SESSION['admin_id'] ?? 0)) {
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("You can't delete your own account."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT name, email, is_active FROM admin_users WHERE id = :id");
    $stmt->execute([':id' => $targetId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("That admin no longer exists."));
        exit;
    }

    // Only a disabled account can be permanently deleted -- matches the UI,
    // which only shows Delete once an account is already disabled, so an
    // active admin is never one click away from permanent removal.
    if ($target['is_active']) {
        header("Location: settings.php?tab=admins&admin_error=" . urlencode("Disable this admin before deleting them."));
        exit;
    }

    $del = $pdo->prepare("DELETE FROM admin_users WHERE id = :id");
    $del->execute([':id' => $targetId]);

    $name = !empty($target['name']) ? $target['name'] : $target['email'];
    header("Location: settings.php?tab=admins&admin_success=" . urlencode("$name was permanently deleted."));
    exit;

} catch (PDOException $e) {
    error_log("delete_admin_user.php database error: " . $e->getMessage());
    header("Location: settings.php?tab=admins&admin_error=" . urlencode("A database error occurred deleting that admin."));
    exit;
}
