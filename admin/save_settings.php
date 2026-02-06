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
                $cleanValue = str_replace(['$', ','], '', $rawValue);
                $valueToSave = $cleanValue === '' ? null : (int)$cleanValue;
            }
            // --- Handle date fields ---
            elseif (in_array($key, ['application_open', 'application_closed', 'review_start', 'review_end', 'announcement_date'])) {
                $valueToSave = $rawValue !== '' ? $rawValue : null;
            }
            // --- Handle string fields ---
            else {
                $valueToSave = $rawValue !== '' ? $rawValue : null;
            }

            // --- DEBUG: browser console log ---
            $jsValue = json_encode([
                'key' => $key,
                'rawValue' => $rawValue,
                'valueToSave' => $valueToSave
            ]);
            echo "<script>console.log('PHP Debug:', $jsValue);</script>";

            // --- DEBUG: server error log ---
            error_log("DEBUG: key = $key, rawValue = '$rawValue', valueToSave = " . var_export($valueToSave, true));

            // --- Prepare and execute PDO ---
            $stmt = $pdo->prepare("
                UPDATE settings
                SET setting_value = :value, updated_at = NOW()
                WHERE setting_key = :key
            ");

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

    // Temporarily comment out redirect so you can see console logs
    // header("Location: settings.php?success=1");
    echo "<p>Settings saved successfully! Check your browser console for debug info.</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating settings: " . $e->getMessage();
}
