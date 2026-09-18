<?php
require_once '../app/db.php';
require_once '../path.php';

// Confirms a committee member's email address (see
// admin/save_committee_member.php, which is what generates this link).
// Final Review can't be sent to a member until confirmed_at is set --
// enforced in admin/send_committee_review.php, not just here.
$token = $_GET['token'] ?? '';

$state = 'invalid'; // invalid | already | confirmed
$memberName = '';

if ($token !== '') {
    try {
        $stmt = $pdo->prepare("SELECT id, name, confirmed_at FROM committee_members WHERE confirmation_token = :token");
        $stmt->execute([':token' => $token]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            $memberName = $member['name'];
            if (!empty($member['confirmed_at'])) {
                $state = 'already';
            } else {
                $upd = $pdo->prepare("UPDATE committee_members SET confirmed_at = NOW() WHERE id = :id");
                $upd->execute([':id' => $member['id']]);
                $state = 'confirmed';
            }
        }
    } catch (Exception $e) {
        error_log("committee/confirm.php error: " . $e->getMessage());
        $state = 'invalid';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../assets/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16.png">
    <link rel="apple-touch-icon" href="../assets/images/apple-touch-icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $state === 'confirmed' ? 'Confirmed' : ($state === 'already' ? 'Already Confirmed' : 'Link Not Valid') ?> - Morgan Legacy Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body { background: rgb(249,250,251); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .gate-card { background: #fff; border: 1px solid rgb(241,242,243); border-radius: 16px; overflow: hidden; max-width: 440px; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .gate-accent { height: 5px; background: linear-gradient(90deg, rgb(7,5,55), #C5A059); }
        .gate-body { padding: 36px 34px; text-align: center; }
        .gate-logo { height: 48px; margin-bottom: 20px; }
        .gate-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 26px; }
        .gate-icon.success { background: rgba(25,135,84,0.12); color: #198754; }
        .gate-icon.warn { background: rgba(220,53,69,0.1); color: #dc3545; }
        .gate-title { font-size: 19px; font-weight: 700; color: #16151f; margin-bottom: 8px; }
        .gate-text { font-size: 14.5px; color: #6c757d; line-height: 1.6; }
    </style>
</head>
<body>
<div class="gate-card">
    <div class="gate-accent"></div>
    <div class="gate-body">
        <img src="../assets/images/logo.png" alt="Morgan Legacy Scholarship" class="gate-logo">

        <?php if ($state === 'confirmed'): ?>
            <div class="gate-icon success"><i class="bi bi-check-lg"></i></div>
            <div class="gate-title">You're confirmed, <?= htmlspecialchars($memberName) ?>!</div>
            <div class="gate-text">Thanks for confirming your email. You're all set to receive the Final Review link whenever it's sent to the committee.</div>
        <?php elseif ($state === 'already'): ?>
            <div class="gate-icon success"><i class="bi bi-check-lg"></i></div>
            <div class="gate-title">Already confirmed</div>
            <div class="gate-text">This email was already confirmed -- there's nothing else you need to do.</div>
        <?php else: ?>
            <div class="gate-icon warn"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="gate-title">This link isn't valid</div>
            <div class="gate-text">It may have been copied incorrectly, or your invite may have been resent since -- check your email for the most recent confirmation link.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
