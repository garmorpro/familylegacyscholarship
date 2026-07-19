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

try {
    // Update the application status to 'reviewed'
    $stmt = $pdo->prepare("
        UPDATE scholarship_applications
        SET application_status = 'reviewed'
        WHERE id = :id
    ");
    $stmt->execute([':id' => $appId]);

    // Redirect back to the application details page
    header("Location: /admin/application_view.php?id=" . $appId);
    exit;

} catch (Exception $e) {
    // Handle errors
    echo "Error updating application status: " . $e->getMessage();
}
