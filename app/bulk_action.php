<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php'; // adjust if needed for $pdo

header('Content-Type: application/json');

try {
    // Decode JSON input
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!$input) {
        throw new Exception('No input received or invalid JSON: ' . $inputJSON);
    }

    $action = $input['action'] ?? null;
    $ids = $input['ids'] ?? [];

    if (!$action || empty($ids)) {
        throw new Exception('No action or IDs provided.');
    }

    // Sanitize IDs
    $ids = $input['ids'] ?? [];
$ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

if (empty($ids)) {
    throw new Exception('No valid application IDs selected.');
}
$idsPlaceholders = implode(',', array_fill(0, count($ids), '?'));


    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM scholarship_applications WHERE id IN ($idsPlaceholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) deleted.";

    } elseif ($action === 'select') {
        $stmt = $pdo->prepare("UPDATE scholarship_applications SET application_status = 'selected' WHERE id IN ($idsPlaceholders)");
        $stmt->execute($ids);
        $message = count($ids) . " application(s) marked as selected.";

    } else {
        throw new Exception('Invalid action: ' . $action);
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
