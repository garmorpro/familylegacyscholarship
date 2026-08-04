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

// The selection email is never sent automatically at designation time --
// the admin picks exactly when it should go out, and a cron job (see
// cron/send_selection_emails.php) is what actually sends it once that time
// arrives. Required so a recipient can never be designated without a
// selection email eventually being scheduled.
//
// A <input type="datetime-local"> value has NO timezone info of its own --
// it's just the wall-clock digits the browser showed, with no indication of
// which zone they're in. Whoever is designating could be in CDT, MDT,
// wherever -- so the browser's actual IANA zone (e.g. "America/Chicago") is
// sent alongside it, and used here to convert to a real, unambiguous UTC
// instant before storing. The cron job then only ever compares UTC to UTC,
// so it fires at the correct moment no matter what timezone the admin (or
// the server itself) happens to be in.
$scheduledSendAtRaw = trim($_POST['scheduled_send_at'] ?? '');
$scheduledSendTz = trim($_POST['scheduled_send_tz'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $scheduledSendAtRaw)) {
    die("Please pick a date and time for the selection email before designating a recipient.");
}
if ($scheduledSendTz === '' || !in_array($scheduledSendTz, DateTimeZone::listIdentifiers(), true)) {
    die("Couldn't determine your time zone. Please try again.");
}

try {
    $localDt = new DateTime(str_replace('T', ' ', $scheduledSendAtRaw), new DateTimeZone($scheduledSendTz));
    $localDt->setTimezone(new DateTimeZone('UTC'));
    $scheduledSendAt = $localDt->format('Y-m-d H:i:s'); // stored as naive UTC
} catch (Exception $e) {
    die("Invalid date/time or time zone provided.");
}

try {
    $pdo->beginTransaction();

    // Only designate applications that are in 'final_review', and only if no
    // *active* final recipient has already been chosen this round. Archived
    // recipients from past cycles must not count here, or no cycle after
    // the first could ever designate a new recipient.
    $stmt = $pdo->prepare("
        UPDATE scholarship_applications
        SET application_status = 'final_recipient'
        WHERE id = :id
          AND application_status = 'final_review'
          AND NOT EXISTS (
              SELECT 1 FROM scholarship_applications
              WHERE application_status = 'final_recipient' AND archived_at IS NULL
          )
    ");
    $stmt->execute([':id' => $appId]);

    if ($stmt->rowCount() > 0) {
        // Create the recipients record immediately, rather than waiting
        // until the round is archived.
        $appStmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE id = :id");
        $appStmt->execute([':id' => $appId]);
        $app = $appStmt->fetch(PDO::FETCH_ASSOC);

        $insertStmt = $pdo->prepare("
            INSERT INTO recipients
            (first_name, last_name, email, phone, expected_graduation_year, intended_school, intended_major, additional_information, date_submitted, application_year, selection_email_scheduled_at, created_at, updated_at)
            VALUES
            (:first_name, :last_name, :email, :phone, :expected_graduation_year, :intended_school, :intended_major, :additional_information, :date_submitted, :application_year, :selection_email_scheduled_at, NOW(), NOW())
        ");
        $insertStmt->execute([
            ':first_name'               => $app['first_name'],
            ':last_name'                => $app['last_name'],
            ':email'                    => $app['email'],
            ':phone'                    => $app['phone'],
            ':expected_graduation_year' => $app['expected_graduation_year'],
            ':intended_school'          => $app['intended_school'],
            ':intended_major'           => $app['intended_major'],
            ':additional_information'   => $app['additional_information'],
            ':date_submitted'           => $app['submitted_at'],
            ':application_year'         => date('Y'),
            ':selection_email_scheduled_at' => $scheduledSendAt,
        ]);

        // A final recipient has been chosen for this cycle -- the committee
        // review link/code, if one was ever sent out, must stop working
        // immediately, and the votes (scoped to this cycle only, with no
        // year/cycle marker of their own) need to be cleared so they don't
        // linger and get mixed up with the next cycle's votes.
        $pdo->exec("DELETE FROM committee_access");
        $pdo->exec("DELETE FROM committee_votes");
    } else {
        error_log("mark_final_selected.php: no-op for id={$appId}, not eligible (wrong status or recipient already chosen)");
    }

    $pdo->commit();

    // Redirect back to the application details page
    header("Location: /admin/application_view.php?id=" . $appId);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("mark_final_selected.php error: " . $e->getMessage());
    echo "Something went wrong updating this application. Please try again.";
}
