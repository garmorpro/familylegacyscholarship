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
            $value = trim($_POST[$key]);

            // Special handling: remove $ sign for award_amount but store as text
            if ($key === 'award_amount') {
                $value = str_replace(['$', ','], '', $value);
            }

            // If empty, store as NULL in DB
            $value = $value !== '' ? $value : null;

            // Update setting in DB
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

    // Redirect back to settings page with success message
    header("Location: settings.php?success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating settings: " . $e->getMessage();
}
