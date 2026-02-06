<?php
require_once '../app/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// Allowed keys
$allowedSettings = [
    'award_amount',        // numeric
    'application_open',    // date
    'application_closed',  // date
    'review_start',        // date
    'review_end',          // date
    'announcement_date',   // date
    'notification_email'   // string
];

try {
    $pdo->beginTransaction();

    foreach ($allowedSettings as $key) {
        if (isset($_POST[$key])) {
            $rawValue = trim($_POST[$key]);
            $valueToSave = null; // default to null

            // --- Handle numeric fields ---
            if ($key === 'award_amount') {
                // Remove $ and commas
                $cleanValue = str_replace(['$', ','], '', $rawValue);
                if ($cleanValue === '') {
                    $valueToSave = null; // empty input -> NULL in DB
                } else {
                    $valueToSave = (int)$cleanValue; // cast to int
                }
            }
            // --- Handle date fields ---
            elseif (in_array($key, ['application_open', 'application_closed', 'review_start', 'review_end', 'announcement_date'])) {
                $valueToSave = $rawValue !== '' ? $rawValue : null; // empty string -> NULL
            }
            // --- Handle string fields ---
            else {
                $valueToSave = $rawValue !== '' ? $rawValue : null; // empty string -> NULL
            }

            // --- DEBUG: log what we're saving ---
            error_log("DEBUG: key = $key, rawValue = '$rawValue', valueToSave = " . var_export($valueToSave, true));

            // --- Prepare statement ---
            $stmt = $pdo->prepare("
                UPDATE settings
                SET setting_value = :value, updated_at = NOW()
                WHERE setting_key = :key
            ");

            // Bind value depending on NULL or not
            if ($valueToSave === null) {
                $stmt->bindValue(':value', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':value', $valueToSave, PDO::PARAM_STR);
            }
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);

            $stmt->execute();
        }
    }

    $pdo->commit();
    header("Location: settings.php?success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating settings: " . $e->getMessage();
}
