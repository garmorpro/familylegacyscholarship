<?php
// Turn off PHP warnings/notices for AJAX requests
ini_set('display_errors', 0);
error_reporting(0);

// Ensure JSON content-type
header('Content-Type: application/json');

require_once '../app/db.php'; // make sure $pdo is initialized
require_once '../app/require_admin.php'; // this endpoint performs destructive DB writes — must be admin-only
require_once '../app/csrf.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('No input received or invalid JSON.');
    }

    if (!csrf_verify($input['csrf_token'] ?? null)) {
        throw new Exception('Security check failed (invalid or expired token). Please refresh the page and try again.');
    }

    $action = $input['action'] ?? null;
    $ids = $input['ids'] ?? [];

    // Ensure valid integer IDs
    $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

    if (!$action) {
        throw new Exception('No action provided.');
    }

    if ($action === 'delete') {
        if (empty($ids)) {
            throw new Exception('No applications selected for deletion.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM scholarship_applications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) deleted.";

    } elseif ($action === 'mark_reviewed') {
        if (empty($ids)) {
            throw new Exception('No applications selected to mark reviewed.');
        }

        // Only advance applications actually in 'submitted' status.
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            UPDATE scholarship_applications
            SET application_status = 'reviewed'
            WHERE id IN ($placeholders) AND application_status = 'submitted' AND archived_at IS NULL
        ");
        $stmt->execute($ids);
        $advancedCount = $stmt->rowCount();
        $skippedCount = count($ids) - $advancedCount;

        $message = $advancedCount . " application(s) marked reviewed.";
        if ($skippedCount > 0) {
            $message .= " " . $skippedCount . " skipped (not in \"Submitted\" status).";
        }

    } elseif ($action === 'select') {
        if (empty($ids)) {
            throw new Exception('No applications selected to advance.');
        }

        // Only advance applications actually in 'reviewed' status — otherwise
        // a still-'submitted' application could skip the review step entirely.
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            UPDATE scholarship_applications
            SET application_status = 'final_review'
            WHERE id IN ($placeholders) AND application_status = 'reviewed' AND archived_at IS NULL
        ");
        $stmt->execute($ids);
        $advancedCount = $stmt->rowCount();
        $skippedCount = count($ids) - $advancedCount;

        $message = $advancedCount . " application(s) advanced to final review.";
        if ($skippedCount > 0) {
            $message .= " " . $skippedCount . " skipped (not in \"Reviewed\" status).";
        }

    } elseif ($action === 'archive') {
        // The recipients record is now created at the moment a final
        // recipient is designated (see mark_final_selected.php), so this no
        // longer needs to copy anything — it just archives the round.
        // Require a final recipient to have been chosen before archiving,
        // as a server-side backstop to the button only being shown then.
        $checkStmt = $pdo->query("SELECT COUNT(*) FROM scholarship_applications WHERE application_status = 'final_recipient' AND archived_at IS NULL");
        if ((int) $checkStmt->fetchColumn() === 0) {
            throw new Exception('Cannot archive until a final recipient has been selected for this round.');
        }

        $stmt = $pdo->prepare("
            UPDATE scholarship_applications
            SET archived_at = NOW()
            WHERE archived_at IS NULL
        ");
        $stmt->execute();
        $archivedCount = $stmt->rowCount();

        $message = $archivedCount . " application(s) archived. They're kept on file and no longer appear on the active dashboard.";
    } else {
        throw new Exception('Invalid action: ' . $action);
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    error_log("bulk_action.php database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
} catch (Exception $e) {
    // Our own validation messages (e.g. "No applications selected") — safe to show as-is.
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
