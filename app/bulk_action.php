<?php
require_once 'functions.php'; // make sure $pdo is available

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
    $ids = $input['ids'] ?? [];

    if (!$action || empty($ids)) {
        throw new Exception('No action or IDs provided.');
    }

    // Sanitize IDs (only integers)
    $ids = array_map('intval', $ids);
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
        throw new Exception('Invalid action.');
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
