<?php
// Shared helper for permanently deleting everything belonging to one
// archived cycle -- its applications, their recommendations, and the
// matching row in the standalone `recipients` copy table (no foreign key
// back to scholarship_applications, so it's matched by the exact email +
// submitted date copied onto it at designation time). Used by both the
// manual "Delete Cycle" button (admin/delete_cycle.php) and automatic
// cycle retention pruning (app/bulk_action.php's archive action).
//
// Manages its own transaction only when the caller isn't already inside
// one, since PostgreSQL doesn't support nested transactions -- callers
// that need several cycles deleted as one all-or-nothing unit should wrap
// their own beginTransaction()/commit() around repeated calls to this.
function delete_archived_cycle(PDO $pdo, string $archivedAt): int {
    $manageTransaction = !$pdo->inTransaction();
    if ($manageTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $idsStmt = $pdo->prepare("SELECT id FROM scholarship_applications WHERE archived_at = :archived_at");
        $idsStmt->execute([':archived_at' => $archivedAt]);
        $ids = $idsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($ids)) {
            if ($manageTransaction) {
                $pdo->rollBack();
            }
            return 0;
        }

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

        if ($manageTransaction) {
            $pdo->commit();
        }
        return $deletedCount;

    } catch (Exception $e) {
        if ($manageTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
