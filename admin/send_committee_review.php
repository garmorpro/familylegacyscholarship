<?php
// Turn off PHP warnings/notices for AJAX requests
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/committee_mailer.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('No input received or invalid JSON.');
    }

    if (!csrf_verify($input['csrf_token'] ?? null)) {
        throw new Exception('Security check failed (invalid or expired token). Please refresh the page and try again.');
    }

    $memberIds = array_filter(array_map('intval', $input['member_ids'] ?? []), fn($id) => $id > 0);

    if (empty($memberIds)) {
        throw new Exception('Select at least one committee member to send to.');
    }

    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    $stmt = $pdo->prepare("SELECT id, name, email FROM committee_members WHERE id IN ($placeholders)");
    $stmt->execute($memberIds);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($members)) {
        throw new Exception('No matching committee members were found.');
    }

    // Every send fully replaces whatever access rows exist -- anyone still
    // holding a link from an older send is locked out until they get a new
    // email, same as before. The difference now is each member gets their
    // own token+code tied to their committee_member_id, so a visitor's
    // identity comes from the link they clicked instead of a self-ID step.
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM committee_access");
    $insert = $pdo->prepare("
        INSERT INTO committee_access (token, code, committee_member_id, created_at)
        VALUES (:token, :code, :member_id, NOW())
    ");

    $credentials = [];
    foreach ($members as $member) {
        $memberToken = bin2hex(random_bytes(32));
        $memberCode = (string) random_int(100000, 999999);

        $insert->execute([
            ':token' => $memberToken,
            ':code' => $memberCode,
            ':member_id' => $member['id'],
        ]);

        $credentials[] = ['member' => $member, 'token' => $memberToken, 'code' => $memberCode];
    }
    $pdo->commit();

    // Emails are sent after the transaction commits -- a slow/failed send
    // shouldn't leave the access rows themselves uncommitted.
    $sentCount = 0;
    $failedNames = [];
    foreach ($credentials as $c) {
        $ok = send_committee_review_email($config, $c['member']['email'], $c['member']['name'], $c['token'], $c['code']);
        if ($ok) {
            $sentCount++;
        } else {
            $failedNames[] = $c['member']['name'];
        }
    }

    $message = $sentCount . " committee member(s) emailed successfully.";
    if ($failedNames) {
        $message .= " Failed to send to: " . implode(', ', $failedNames) . ".";
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("send_committee_review.php database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
