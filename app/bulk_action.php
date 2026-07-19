<?php
// Turn off PHP warnings/notices for AJAX requests
ini_set('display_errors', 0);
error_reporting(0);

// Ensure JSON content-type
header('Content-Type: application/json');

require_once '../app/db.php'; // make sure $pdo is initialized
require_once '../app/require_admin.php'; // this endpoint performs destructive DB writes — must be admin-only
require_once '../app/csrf.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('No input received or invalid JSON.');
    }

    if (!csrf_verify($input['csrf_token'] ?? null)) {
        throw new Exception('Security check failed (invalid or expired token). Please refresh the page and try again.');
    }

    $action = $input['action'] ?? null;
    $ids = $input['ids'] ?? [];

    // Ensure valid integer IDs
    $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

    if (!$action) {
        throw new Exception('No action provided.');
    }

    if ($action === 'delete') {
        if (empty($ids)) {
            throw new Exception('No applications selected for deletion.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM scholarship_applications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) deleted.";

    } elseif ($action === 'select') {
        if (empty($ids)) {
            throw new Exception('No applications selected to advance.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE scholarship_applications SET application_status = 'final_review' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) advanced to final review.";

    } elseif ($action === 'bulk_delete') {
    // Step 1: Get all applications with application_status = 'final_recipient'
    $stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE application_status = 'final_recipient'");
    $stmt->execute();
    $finalRecipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 2: Insert each into the recipients table
    $insertStmt = $pdo->prepare("
        INSERT INTO recipients 
        (first_name, last_name, email, phone, expected_graduation_year, intended_school, intended_major, additional_information, date_submitted, application_year, created_at, updated_at)
        VALUES 
        (:first_name, :last_name, :email, :phone, :expected_graduation_year, :intended_school, :intended_major, :additional_information, :date_submitted, :application_year, NOW(), NOW())
    ");

    foreach ($finalRecipients as $app) {
        $insertStmt->execute([
            ':first_name' => $app['first_name'],
            ':last_name' => $app['last_name'],
            ':email' => $app['email'],
            ':phone' => $app['phone'],
            ':expected_graduation_year' => $app['expected_graduation_year'],
            ':intended_school' => $app['intended_school'],
            ':intended_major' => $app['intended_major'],
            ':additional_information' => $app['additional_information'],
            ':date_submitted' => $app['submitted_at'],
            ':application_year' => date('Y')
        ]);
    }

    // Step 3: Delete all applications except final recipients
    $stmt = $pdo->prepare("DELETE FROM scholarship_applications");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();

    $message = $deletedCount . " application(s) deleted and " . count($finalRecipients) . " recipient saved.";
} else {
        throw new Exception('Invalid action: ' . $action);
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
