<?php
// Checks for recipients whose selection email has been scheduled and is
// due, and sends it. Meant to run from the server's crontab every 5
// minutes -- not reachable over HTTP, CLI only.
//
// Crontab line (adjust the path to match the server):
//   */5 * * * * /usr/bin/php /path/to/familylegacyscholarship/cron/send_selection_emails.php >> /path/to/familylegacyscholarship/cron/send_selection_emails.log 2>&1

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/recipient_selection_mailer.php';

// selection_email_scheduled_at is stored as a naive UTC timestamp (see
// mark_final_selected.php, which converts whatever time zone the admin's
// browser was actually in at the moment they picked it into UTC before
// saving). gmdate() always returns UTC regardless of this server's own
// timezone setting, so this comparison is correct no matter what zone the
// box running cron happens to be in.
$nowUtc = gmdate('Y-m-d H:i:s');

try {
    $stmt = $pdo->prepare("
        SELECT * FROM recipients
        WHERE selection_email_scheduled_at IS NOT NULL
          AND selection_email_scheduled_at <= :now
          AND selection_email_sent_at IS NULL
    ");
    $stmt->execute([':now' => $nowUtc]);
    $due = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($due)) {
        exit(0);
    }

    $awardAmountRaw = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'award_amount'")->fetchColumn();
    $awardAmount = $awardAmountRaw !== false ? (string) $awardAmountRaw : '';

    // Stamped in UTC too (rather than the DB server's own NOW(), whose
    // timezone isn't guaranteed) so every timestamp on this table follows
    // the same convention and displays correctly everywhere.
    $markSent = $pdo->prepare("UPDATE recipients SET selection_email_sent_at = :now WHERE id = :id");

    foreach ($due as $recipient) {
        $ok = send_recipient_selection_email(
            $config,
            $recipient['email'],
            $recipient['first_name'],
            $awardAmount
        );

        if ($ok) {
            $markSent->execute([':id' => $recipient['id'], ':now' => gmdate('Y-m-d H:i:s')]);
            echo "[" . date('Y-m-d H:i:s') . "] Sent selection email to {$recipient['email']} (recipient #{$recipient['id']}).\n";
        } else {
            error_log("send_selection_emails.php: failed to send to {$recipient['email']} (recipient #{$recipient['id']})");
            echo "[" . date('Y-m-d H:i:s') . "] FAILED sending to {$recipient['email']} (recipient #{$recipient['id']}).\n";
        }
    }
} catch (PDOException $e) {
    error_log("send_selection_emails.php database error: " . $e->getMessage());
    exit(1);
}
