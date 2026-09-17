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

// Only these two steps can be undone -- reverting out of final_recipient
// would also need to unwind the recipients row and any scheduled selection
// email that mark_final_selected.php creates, which this doesn't handle.
$previousStatus = [
    'reviewed'     => 'submitted',
    'final_review' => 'reviewed',
];

try {
    $stmt = $pdo->prepare("SELECT application_status FROM scholarship_applications WHERE id = :id AND archived_at IS NULL");
    $stmt->execute([':id' => $appId]);
    $currentStatus = $stmt->fetchColumn();

    if ($currentStatus !== false && isset($previousStatus[$currentStatus])) {
        $stmt = $pdo->prepare("
            UPDATE scholarship_applications
            SET application_status = :previous
            WHERE id = :id AND application_status = :current AND archived_at IS NULL
        ");
        $stmt->execute([
            ':previous' => $previousStatus[$currentStatus],
            ':id' => $appId,
            ':current' => $currentStatus,
        ]);
    } else {
        error_log("revert_status.php: no-op for id={$appId}, status '{$currentStatus}' is not revertible");
    }

    // The row-level quick action in the applications table wants to stay on
    // that page instead of jumping to the detail view.
    $returnTo = ($_POST['return'] ?? '') === 'index'
        ? "/admin/index.php"
        : "/admin/application_view.php?id=" . $appId;
    header("Location: " . $returnTo);
    exit;

} catch (Exception $e) {
    error_log("revert_status.php error: " . $e->getMessage());
    echo "Something went wrong updating this application. Please try again.";
}
