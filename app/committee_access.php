<?php
// Shared gate for the public committee-review pages (committee/review.php,
// committee/review_application.php). Include this after db.php and after
// $token has been set from $_GET['token'] -- it either falls through
// silently (access granted, $committeeAccess/$committeeMemberId/
// $committeeMemberName are set) or renders the blocked/code-entry page
// itself and exits.
//
// Each committee member gets their own token+code tied to their
// committee_member_id (see admin/send_committee_review.php), so identity
// comes from the link they clicked -- no self-ID step needed. Sending a
// new round of invites deletes all old rows and inserts fresh ones, and
// designating a final recipient deletes them outright, so a stale link
// or code simply stops matching anything once that happens.

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/spam_protection.php';

// Before this per-member rewrite, committee_code_verified held a single
// scalar code, not a token-keyed array -- a browser session still carrying
// that old shape would fail every array lookup below (silently, since
// array access on a string just returns null) and get asked for the code
// forever. Reset it back to an array if it's ever anything else.
if (!isset($_SESSION['committee_code_verified']) || !is_array($_SESSION['committee_code_verified'])) {
    $_SESSION['committee_code_verified'] = [];
}

if (empty($token)) {
    committee_gate_blocked("This link isn't valid.");
}

$accessStmt = $pdo->prepare("
    SELECT committee_access.*, committee_members.name AS member_name
    FROM committee_access
    JOIN committee_members ON committee_members.id = committee_access.committee_member_id
    WHERE committee_access.token = :token
    LIMIT 1
");
$accessStmt->execute([':token' => $token]);
$committeeAccess = $accessStmt->fetch(PDO::FETCH_ASSOC);

if (!$committeeAccess) {
    committee_gate_blocked("This review is no longer available. A final recipient may already have been selected, or a newer invitation was sent since this link went out -- check your email for the most recent one.");
}

// Verification state is keyed by token, not just a bare session flag --
// this link is one specific person's, so there's no shared code to mix up
// across members the way a single global code would.
$codeVerified = isset($_SESSION['committee_code_verified'][$token])
    && hash_equals((string) $committeeAccess['code'], (string) $_SESSION['committee_code_verified'][$token]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    // A 6-digit code is only ~1M combinations -- without this, someone who
    // got hold of just the link (forwarded email, shared screen, browser
    // history) but not the code could script their way through all of them.
    // Same per-IP limiter the public forms use, keyed separately so it
    // doesn't share a budget with anything else.
    if (is_rate_limited($pdo, 'committee_code', 8, 15)) {
        committee_gate_code_form($token, "Too many attempts. Please wait a while before trying again.");
    }

    record_submission_attempt($pdo, 'committee_code');

    if (hash_equals((string) $committeeAccess['code'], trim((string) $_POST['code']))) {
        $_SESSION['committee_code_verified'][$token] = $committeeAccess['code'];
        $codeVerified = true;
    } else {
        committee_gate_code_form($token, "That code doesn't match. Double-check the email and try again.");
    }
}

if (!$codeVerified) {
    committee_gate_code_form($token);
}

$committeeMemberId = (int) $committeeAccess['committee_member_id'];
$committeeMemberName = $committeeAccess['member_name'];

// Falls through here only when access is fully verified.

function committee_gate_page_start(string $title): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="/assets/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16.png">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> - Morgan Legacy Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body { background: rgb(249,250,251); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .gate-card { background: #fff; border: 1px solid rgb(241,242,243); border-radius: 16px; overflow: hidden; max-width: 440px; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .gate-accent { height: 5px; background: linear-gradient(90deg, rgb(7,5,55), #C5A059); }
        .gate-body { padding: 36px 34px; text-align: center; }
        .gate-logo { height: 48px; margin-bottom: 20px; }
        .gate-title { font-size: 19px; font-weight: 700; color: #16151f; margin-bottom: 8px; }
        .gate-text { font-size: 14.5px; color: #6c757d; line-height: 1.6; margin-bottom: 22px; }
        .gate-code-input { text-align: center; font-size: 22px; letter-spacing: 0.3em; font-weight: 700; padding: 12px; border-radius: 8px; border: 1px solid #ced4da; width: 100%; }
        .gate-btn { background: rgb(7,5,55); color: #fff; border: none; padding: 12px 26px; border-radius: 8px; font-weight: 600; font-size: 14.5px; width: 100%; margin-top: 16px; }
        .gate-btn:hover { background: rgb(20,16,80); color: #fff; }
        .gate-error { background: rgba(220,53,69,0.08); color: #dc3545; border-radius: 8px; padding: 10px 14px; font-size: 13.5px; margin-bottom: 18px; }
    </style>
</head>
<body>
<div class="gate-card">
    <div class="gate-accent"></div>
    <div class="gate-body">
        <img src="/assets/images/logo.png" alt="Morgan Legacy Scholarship" class="gate-logo">
<?php
}

function committee_gate_page_end(): void {
?>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

function committee_gate_blocked(string $message): void {
    committee_gate_page_start('Review Unavailable');
    ?>
        <div class="gate-title">This review isn't available</div>
        <div class="gate-text"><?= htmlspecialchars($message) ?></div>
    <?php
    committee_gate_page_end();
}

function committee_gate_code_form(string $token, ?string $error = null): void {
    committee_gate_page_start('Enter Access Code');
    ?>
        <div class="gate-title">Enter your access code</div>
        <div class="gate-text">Check the email you received for the 6-digit code that goes with this review link.</div>
        <?php if ($error): ?>
            <div class="gate-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="?token=<?= urlencode($token) ?>">
            <input type="text" name="code" class="gate-code-input" maxlength="6" inputmode="numeric" autocomplete="off" autofocus placeholder="000000">
            <button type="submit" class="gate-btn">Continue</button>
        </form>
    <?php
    committee_gate_page_end();
}
