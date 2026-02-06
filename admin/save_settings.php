<?php
require_once '../app/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// Allowed keys
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

    foreach ($allowedSettings as $key) {
    if (isset($_POST[$key])) {
        $value = trim($_POST[$key]);

        // Remove $ and commas for award_amount
        if ($key === 'award_amount') {
            $value = str_replace(['$', ','], '', $value);
            // Convert empty string to NULL
            $value = $value === '' ? null : $value;
        }

        // Convert empty strings to NULL for other fields if needed
        if ($key !== 'award_amount') {
            $value = $value === '' ? null : $value;
        }

        $stmt = $pdo->prepare("
            UPDATE settings
            SET setting_value = :value, updated_at = NOW()
            WHERE setting_key = :key
        ");

        if ($value === null) {
            $stmt->bindValue(':value', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':value', $value, PDO::PARAM_STR);
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
