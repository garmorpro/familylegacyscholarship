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
    'notification_email'
];

try {
    $pdo->beginTransaction();

    // Prepare statement once
    $stmt = $pdo->prepare("
        UPDATE settings
        SET setting_value = :value, updated_at = NOW()
        WHERE setting_key = :key
    ");

    foreach ($allowedSettings as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);

            // For numeric fields like award_amount, remove $ and commas
            if ($key === 'award_amount') {
                $value = str_replace(['$', ','], '', $value);

                // Optional: ensure numeric
                if (!is_numeric($value)) {
                    throw new Exception("Invalid value for award amount.");
                }
            }

            // Never store NULL in NOT NULL column, store empty string instead
            $valueToSave = $value !== '' ? $value : '';

            $stmt->bindValue(':value', $valueToSave, PDO::PARAM_STR);
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    $pdo->commit();

    // Redirect back with success
    header("Location: settings.php?success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    // Redirect back with error message
    $error = urlencode($e->getMessage());
    header("Location: settings.php?error=$error");
    exit;
}
