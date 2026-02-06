<?php
require_once '../app/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// List of allowed settings keys
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
            $value = $_POST[$key] !== '' ? $_POST[$key] : null;

            // Update setting
            $stmt = $pdo->prepare("
                UPDATE settings
                SET setting_value = :value, updated_at = NOW()
                WHERE setting_key = :key
            ");
            $stmt->execute([
                ':value' => $value,
                ':key'   => $key
            ]);
        }
    }

    $pdo->commit();

    // Redirect back to settings page with success
    header("Location: settings.php?success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating settings: " . $e->getMessage();
}
