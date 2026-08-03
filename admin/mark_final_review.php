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
    // Final review has an admin-configurable cap (Settings > Review Limits).
    // Default of 10 matches the fallback shown on the settings page itself.
    $limitValue = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'final_review_limit'")->fetchColumn();
    $finalReviewLimit = ($limitValue !== false && ctype_digit((string) $limitValue)) ? (int) $limitValue : 10;

    // Only advance applications that are actually still 'reviewed' and
    // active, and only while final review is under its cap.
    $stmt = $pdo->prepare("
        UPDATE scholarship_applications
        SET application_status = 'final_review'
        WHERE id = :id AND application_status = 'reviewed' AND archived_at IS NULL
          AND (SELECT COUNT(*) FROM scholarship_applications WHERE application_status = 'final_review' AND archived_at IS NULL) < :limit
    ");
    $stmt->execute([':id' => $appId, ':limit' => $finalReviewLimit]);

    if ($stmt->rowCount() === 0) {
        error_log("mark_final_review.php: no-op for id={$appId}, not in 'reviewed' status or final review limit reached");
    }

    // The row-level quick action in the applications table wants to stay on
    // that page instead of jumping to the detail view.
    $returnTo = ($_POST['return'] ?? '') === 'index'
        ? "/admin/index.php"
        : "/admin/application_view.php?id=" . $appId;
    header("Location: " . $returnTo);
    exit;

} catch (Exception $e) {
    error_log("mark_final_review.php error: " . $e->getMessage());
    echo "Something went wrong updating this application. Please try again.";
}
