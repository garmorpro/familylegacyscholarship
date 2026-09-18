<?php
// Turn off PHP warnings/notices for AJAX requests
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('No input received or invalid JSON.');
    }

    if (!csrf_verify($input['csrf_token'] ?? null)) {
        throw new Exception('Security check failed (invalid or expired token). Please refresh the page and try again.');
    }

    $recipientId = $input['recipient_id'] ?? null;
    if (!$recipientId || !ctype_digit((string) $recipientId)) {
        throw new Exception('Invalid recipient.');
    }

    // Same conversion mark_final_selected.php uses: a <input type="datetime-local">
    // value carries no timezone of its own, so the browser's actual IANA
    // zone is sent alongside it and used to convert to a real, unambiguous
    // UTC instant before storing -- the cron job then only ever compares
    // UTC to UTC.
    $rawDateTime = trim($input['scheduled_send_at'] ?? '');
    $tz = trim($input['scheduled_send_tz'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $rawDateTime)) {
        throw new Exception('Please pick a valid date and time.');
    }
    if ($tz === '' || !in_array($tz, DateTimeZone::listIdentifiers(), true)) {
        throw new Exception("Couldn't determine your time zone. Please try again.");
    }

    $localDt = new DateTime(str_replace('T', ' ', $rawDateTime), new DateTimeZone($tz));
    $localDt->setTimezone(new DateTimeZone('UTC'));
    $scheduledSendAt = $localDt->format('Y-m-d H:i:s'); // stored as naive UTC

    // Can't reschedule an email that's already gone out.
    $sentStmt = $pdo->prepare("SELECT selection_email_sent_at FROM recipients WHERE id = :id");
    $sentStmt->execute([':id' => $recipientId]);
    $sentAt = $sentStmt->fetchColumn();

    if ($sentAt === false) {
        throw new Exception('Recipient not found.');
    }
    if (!empty($sentAt)) {
        throw new Exception("This recipient's selection email has already been sent -- the schedule can no longer be changed.");
    }

    $stmt = $pdo->prepare("
        UPDATE recipients
        SET selection_email_scheduled_at = :scheduled_at, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':scheduled_at' => $scheduledSendAt, ':id' => $recipientId]);

    echo json_encode(['success' => true, 'message' => 'Selection email schedule updated.']);

} catch (PDOException $e) {
    error_log("update_recipient_schedule.php database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
