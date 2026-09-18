<?php
// Turn off PHP warnings/notices for AJAX requests
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/spam_protection.php';
require_once __DIR__ . '/draft_mailer.php';
require_once __DIR__ . '/../path.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    csrf_require();

    // Same protections the real submission uses (see application-form.php),
    // reusing the same "form rendered at" session timestamp -- saving a
    // draft can be abused the exact same way submitting the real form can
    // (here, mass-emailing resume links to addresses that aren't the
    // sender's own), so it needs the same guardrails, not lighter ones.
    if (is_honeypot_filled()) {
        error_log("save_draft.php: honeypot triggered from " . get_client_ip());
        echo json_encode(['success' => true, 'message' => 'Saved.']);
        exit;
    }
    if (submission_too_fast('app_form_started_at')) {
        echo json_encode(['success' => false, 'message' => 'Please take a moment filling out the form before saving.']);
        exit;
    }
    if (is_rate_limited($pdo, 'draft_save')) {
        echo json_encode(['success' => false, 'message' => 'Too many save attempts from your network recently. Please try again in a little while.']);
        exit;
    }
    record_submission_attempt($pdo, 'draft_save');

    // Opportunistic cleanup, same 1-in-20 pattern as submission_attempts:
    // once the cycle is no longer open, a saved draft can never be resumed
    // or submitted anyway (see the cycleState gate in application-form.php),
    // so there's no reason for the PII in it to keep sitting in the table.
    if (random_int(1, 20) === 1) {
        try {
            $closeSetting = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'application_closed'")->fetchColumn();
            if (!empty($closeSetting) && date('Y-m-d') > $closeSetting) {
                $pdo->exec("DELETE FROM application_drafts");
            }
        } catch (PDOException $e) {
            error_log("save_draft.php draft cleanup failed: " . $e->getMessage());
        }
    }

    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address before saving.']);
        exit;
    }

    // Whitelisted, hardcoded column names -- never built from request data,
    // so interpolating them into the SQL below is safe.
    $fields = [
        'first_name', 'last_name', 'phone', 'expected_graduation_year', 'gpa',
        'institution_type', 'intended_school', 'intended_major', 'extracurricular',
        'leadership', 'community_service', 'essay', 'recommender_name',
        'recommender_email', 'recommender_relationship', 'financial_need', 'additional_information',
    ];
    $values = [];
    foreach ($fields as $field) {
        $values[":{$field}"] = $_POST[$field] ?? '';
    }

    // If this save came from a page that was itself resumed from a draft
    // (or a second save in the same sitting), update that same row instead
    // of leaving a trail of duplicate drafts behind.
    $existingToken = trim($_POST['draft_token'] ?? '');
    $isUpdate = false;
    if ($existingToken !== '') {
        $checkStmt = $pdo->prepare("SELECT id FROM application_drafts WHERE token = :token");
        $checkStmt->execute([':token' => $existingToken]);
        $isUpdate = (bool) $checkStmt->fetchColumn();
    }

    if ($isUpdate) {
        $token = $existingToken;
        $setSql = implode(', ', array_map(fn($f) => "{$f} = :{$f}", $fields));
        $stmt = $pdo->prepare("UPDATE application_drafts SET {$setSql}, email = :email, updated_at = NOW() WHERE token = :token");
        $stmt->execute(array_merge($values, [':email' => $email, ':token' => $token]));
    } else {
        $token = bin2hex(random_bytes(32));
        $columns = array_merge(['token', 'email'], $fields);
        $placeholders = array_merge([':token', ':email'], array_map(fn($f) => ":{$f}", $fields));
        $stmt = $pdo->prepare("INSERT INTO application_drafts (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")");
        $stmt->execute(array_merge([':token' => $token, ':email' => $email], $values));
    }

    // The resume link is tied to the token, not to any one save -- once
    // it's been emailed the first time, updating the same draft again
    // (e.g. after resuming and making more progress) only needs to update
    // the saved data. Re-emailing every save would mean a fresh "here's
    // your link" email each time, when the one they already have still
    // points at the same (now newer) data.
    if ($isUpdate) {
        echo json_encode(['success' => true, 'message' => 'Your progress has been updated. The link already in your email still works to pick up from here.', 'token' => $token]);
        exit;
    }

    $resumeLink = BASE_URL . "/application-form.php?resume=" . urlencode($token);
    $firstName = trim($values[':first_name']) !== '' ? $values[':first_name'] : 'there';

    $sent = send_draft_resume_email($config, $email, $firstName, $resumeLink);

    if (!$sent) {
        echo json_encode(['success' => false, 'message' => "We saved your progress, but couldn't email your resume link just now. Please try saving again in a moment."]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Check your email for your unique link to finish this application later.', 'token' => $token]);

} catch (PDOException $e) {
    error_log("save_draft.php database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred saving your progress. Please try again.']);
} catch (Exception $e) {
    error_log("save_draft.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong saving your progress. Please try again.']);
}
