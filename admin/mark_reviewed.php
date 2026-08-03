<?php
require '../app/db.php'; // Make sure this points to your PDO connection
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

csrf_require();

// Check if ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid application ID.");
}

$appId = (int)$_POST['id'];

try {
    // Only advance applications that are actually still 'submitted' and active
    $stmt = $pdo->prepare("
        UPDATE scholarship_applications
        SET application_status = 'reviewed'
        WHERE id = :id AND application_status = 'submitted' AND archived_at IS NULL
    ");
    $stmt->execute([':id' => $appId]);

    if ($stmt->rowCount() === 0) {
        error_log("mark_reviewed.php: no-op for id={$appId}, not in 'submitted' status");
    }

    // The row-level quick action in the applications table wants to stay on
    // that page instead of jumping to the detail view.
    $returnTo = ($_POST['return'] ?? '') === 'index'
        ? "/admin/index.php"
        : "/admin/application_view.php?id=" . $appId;
    header("Location: " . $returnTo);
    exit;

} catch (Exception $e) {
    error_log("mark_reviewed.php error: " . $e->getMessage());
    echo "Something went wrong updating this application. Please try again.";
}
