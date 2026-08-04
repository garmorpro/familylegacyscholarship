<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    die("Invalid recipient ID.");
}

$recipientId = (int) $_POST['id'];

try {
    // Grab the picture filename first so it can be cleaned up off disk too
    // -- never trust a filename from the request itself.
    $stmt = $pdo->prepare("SELECT recipient_picture FROM recipients WHERE id = :id");
    $stmt->execute([':id' => $recipientId]);
    $picture = $stmt->fetchColumn();

    $deleteStmt = $pdo->prepare("DELETE FROM recipients WHERE id = :id");
    $deleteStmt->execute([':id' => $recipientId]);

    if ($picture) {
        $file = __DIR__ . '/../uploads/recipients/' . $picture;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    header("Location: /admin/recipients.php?success=" . urlencode("Recipient removed."));
    exit;

} catch (PDOException $e) {
    error_log("delete_recipient.php database error: " . $e->getMessage());
    header("Location: /admin/recipients.php?error=" . urlencode("A database error occurred removing that recipient."));
    exit;
}
