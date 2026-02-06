<?php
// Turn off PHP warnings/notices for AJAX requests
ini_set('display_errors', 0);
error_reporting(0);

// Ensure JSON content-type
header('Content-Type: application/json');

require_once '../app/db.php'; // make sure $pdo is initialized

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('No input received or invalid JSON.');
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
        // Delete all applications except those with final_recipient status
        $stmt = $pdo->prepare("DELETE FROM scholarship_applications WHERE application_status != 'final_recipient'");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        $message = $deletedCount . " application(s) deleted (excluding final recipients).";

    } else {
        throw new Exception('Invalid action: ' . $action);
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
