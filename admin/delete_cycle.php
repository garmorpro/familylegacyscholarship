<?php
require '../app/db.php'; // Make sure this points to your PDO connection
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

csrf_require();

// Same archived_at identifier archives.php / archive_cycle.php use, taken
// as-is and unreformatted so it matches exactly.
$archivedAt = $_POST['archived_at'] ?? '';

if ($archivedAt === '') {
    header("Location: archives.php?cycle_error=" . urlencode("No cycle specified."));
    exit;
}

try {
    $pdo->beginTransaction();

    $idsStmt = $pdo->prepare("SELECT id FROM scholarship_applications WHERE archived_at = :archived_at");
    $idsStmt->execute([':archived_at' => $archivedAt]);
    $ids = $idsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ids)) {
        $pdo->rollBack();
        header("Location: archives.php?cycle_error=" . urlencode("That cycle couldn't be found -- it may have already been deleted."));
        exit;
    }

    // The recipients table is a standalone copy made at the moment a
    // recipient was designated (see mark_final_selected.php) -- it has no
    // foreign key back here, so it's matched by the exact email + submitted
    // date copied onto it then, and removed too so it doesn't outlive the
    // cycle it came from.
    $recipientStmt = $pdo->prepare("
        SELECT email, submitted_at FROM scholarship_applications
        WHERE archived_at = :archived_at AND application_status = 'final_recipient'
    ");
    $recipientStmt->execute([':archived_at' => $archivedAt]);
    $recipient = $recipientStmt->fetch(PDO::FETCH_ASSOC);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM recommendations WHERE scholarship_application_id IN ($placeholders)")->execute($ids);

    if ($recipient) {
        $pdo->prepare("DELETE FROM recipients WHERE email = :email AND date_submitted = :date_submitted")
            ->execute([':email' => $recipient['email'], ':date_submitted' => $recipient['submitted_at']]);
    }

    $deleteStmt = $pdo->prepare("DELETE FROM scholarship_applications WHERE archived_at = :archived_at");
    $deleteStmt->execute([':archived_at' => $archivedAt]);
    $deletedCount = $deleteStmt->rowCount();

    $pdo->commit();

    header("Location: archives.php?cycle_success=" . urlencode("Cycle deleted -- {$deletedCount} application(s) permanently removed."));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("delete_cycle.php error: " . $e->getMessage());
    header("Location: archives.php?cycle_error=" . urlencode("Something went wrong deleting that cycle. Please try again."));
    exit;
}
