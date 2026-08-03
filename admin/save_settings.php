<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

// Ensure PDO exists
if (!isset($pdo)) {
    die("PDO connection not initialized!");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

csrf_require();

// Allowed keys to update
$allowedSettings = [
    'award_amount',
    'application_open',
    'application_closed',
    'review_start',
    'review_end',
    'announcement_date',
    'notification_email',
    'essay_prompt',
    'final_review_limit'
];

// Each field is validated and saved independently, so one bad field
// (e.g. a non-numeric award amount) can't silently roll back every other
// change in the same submission -- previously a single throw here aborted
// the whole transaction, including fields that had nothing wrong with them.
$fieldErrors = [];

try {
    $stmt = $pdo->prepare("
        UPDATE settings
        SET setting_value = :value, updated_at = NOW()
        WHERE setting_key = :key
    ");

    foreach ($allowedSettings as $key) {
        if (!isset($_POST[$key])) {
            continue;
        }

        $value = trim($_POST[$key]);

        // For numeric fields like award_amount, remove $ and commas
        if ($key === 'award_amount') {
            $value = str_replace(['$', ','], '', $value);

            if ($value !== '' && !is_numeric($value)) {
                $fieldErrors[] = "Award amount must be a number -- that field was left unchanged.";
                continue; // skip only this field; keep saving the rest
            }
        }

        // Final review limit must be a positive whole number -- the whole
        // point of this setting is to cap the pipeline, so an empty or
        // invalid value would silently defeat that.
        if ($key === 'final_review_limit') {
            if (!ctype_digit($value) || (int) $value < 1) {
                $fieldErrors[] = "Final review limit must be a whole number of 1 or more -- that field was left unchanged.";
                continue;
            }
        }

        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
    }

    if ($fieldErrors) {
        header("Location: settings.php?error=" . urlencode(implode(' ', $fieldErrors)));
    } else {
        header("Location: settings.php?success=1");
    }
    exit;

} catch (PDOException $e) {
    error_log("save_settings.php database error: " . $e->getMessage());
    header("Location: settings.php?error=" . urlencode("A database error occurred saving settings."));
    exit;
}
