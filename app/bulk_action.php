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

    if (!$action || empty($ids)) {
        throw new Exception('No valid action or IDs provided.');
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM scholarship_applications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) deleted.";
    } elseif ($action === 'select') {
        $stmt = $pdo->prepare("UPDATE scholarship_applications SET application_status = 'final_review' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) advanced to final review.";
    } else {
        throw new Exception('Invalid action: ' . $action);
    }

    echo json_encode(['success' => true, 'message' => $message]);

    // Important: do NOT close PHP tag or echo anything else

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
