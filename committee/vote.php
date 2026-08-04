<?php
header('Content-Type: application/json');

require_once '../app/db.php';
require_once '../path.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
require_once '../app/committee_access.php';
// Falls through only once token, code, and identity are all established.
// $committeeMemberId is now available.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$applicationId = (int) ($input['application_id'] ?? 0);

if ($applicationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'No applicant specified.']);
    exit;
}

try {
    // Only allow voting for an applicant that's actually still in Final
    // Review right now -- same defense-in-depth restriction as the
    // read-only detail page.
    $checkStmt = $pdo->prepare("
        SELECT id FROM scholarship_applications
        WHERE id = :id AND application_status = 'final_review' AND archived_at IS NULL
    ");
    $checkStmt->execute([':id' => $applicationId]);

    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'That applicant is no longer available to vote for.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO committee_votes (committee_member_id, application_id, created_at, updated_at)
        VALUES (:member_id, :app_id, NOW(), NOW())
        ON CONFLICT (committee_member_id)
        DO UPDATE SET application_id = :app_id, updated_at = NOW()
    ");
    $stmt->execute([':member_id' => $committeeMemberId, ':app_id' => $applicationId]);

    echo json_encode(['success' => true, 'message' => 'Your pick has been recorded.']);

} catch (PDOException $e) {
    error_log("vote.php database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred recording your pick.']);
}
