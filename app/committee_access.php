<?php
// Shared gate for the public committee-review pages (committee/review.php,
// committee/review_application.php). Include this after db.php and after
// $token has been set from $_GET['token'] -- it either falls through
// silently (access granted, $committeeAccess holds the active row) or
// renders the blocked/code-entry page itself and exits.
//
// There is only ever one active committee_access row at a time: sending a
// new invite deletes the old row and inserts a fresh token+code, and
// designating a final recipient deletes the row outright. So a visitor's
// session is only ever valid for the row that existed when they last
// entered the code correctly -- if the code has rotated or the row is
// gone, the stored session value simply won't match anymore and they're
// asked again (or told the review has ended).

session_start();

if (empty($token)) {
    committee_gate_blocked("This link isn't valid.");
}

$accessStmt = $pdo->prepare("SELECT * FROM committee_access WHERE token = :token LIMIT 1");
$accessStmt->execute([':token' => $token]);
$committeeAccess = $accessStmt->fetch(PDO::FETCH_ASSOC);

if (!$committeeAccess) {
    committee_gate_blocked("This review is no longer available. A final recipient may already have been selected, or a newer invitation was sent since this link went out -- check your email for the most recent one.");
}

$codeVerified = isset($_SESSION['committee_code_verified'])
    && hash_equals((string) $committeeAccess['code'], (string) $_SESSION['committee_code_verified']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    if (hash_equals((string) $committeeAccess['code'], trim((string) $_POST['code']))) {
        $_SESSION['committee_code_verified'] = $committeeAccess['code'];
        $codeVerified = true;
    } else {
        committee_gate_code_form($token, "That code doesn't match. Double-check the email and try again.");
    }
}

if (!$codeVerified) {
    committee_gate_code_form($token);
}

// Code verified. Now establish *who* this visitor is -- the shared
// link/code alone doesn't distinguish committee members from each other,
// so votes need a self-identification step. Supports switching identity
// (e.g. wrong person clicked) via ?switch_identity=1.

if (isset($_GET['switch_identity'])) {
    unset($_SESSION['committee_member_id']);
}

$membersStmt = $pdo->query("SELECT id, name, email FROM committee_members ORDER BY name");
$committeeMemberRoster = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_id'])) {
    $chosenId = (int) $_POST['member_id'];
    $matches = array_filter($committeeMemberRoster, fn($m) => (int) $m['id'] === $chosenId);
    if ($matches) {
        $_SESSION['committee_member_id'] = $chosenId;
    } else {
        committee_gate_identity_form($token, $committeeMemberRoster, "Please choose a name from the list.");
    }
}

$committeeMemberId = $_SESSION['committee_member_id'] ?? null;

// Guards against a stale session pointing at a member who's since been
// removed from the roster.
if ($committeeMemberId !== null && !array_filter($committeeMemberRoster, fn($m) => (int) $m['id'] === (int) $committeeMemberId)) {
    unset($_SESSION['committee_member_id']);
    $committeeMemberId = null;
}

if ($committeeMemberId === null) {
    committee_gate_identity_form($token, $committeeMemberRoster);
}

$committeeMemberName = '';
foreach ($committeeMemberRoster as $m) {
    if ((int) $m['id'] === (int) $committeeMemberId) {
        $committeeMemberName = $m['name'];
        break;
    }
}

// Falls through here only when access is fully verified and identity is known.

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

function committee_gate_identity_form(string $token, array $roster, ?string $error = null): void {
    committee_gate_page_start('Who Are You?');
    ?>
        <div class="gate-title">Which of these are you?</div>
        <div class="gate-text">This is how your pick gets attributed once you select a candidate. You can switch this later if needed.</div>
        <?php if ($error): ?>
            <div class="gate-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (empty($roster)): ?>
            <div class="gate-text">No committee roster has been set up yet -- check back once one has.</div>
        <?php else: ?>
            <form method="POST" action="?token=<?= urlencode($token) ?>">
                <div class="text-start" style="max-height: 260px; overflow-y: auto; margin-bottom: 8px;">
                    <?php foreach ($roster as $m): ?>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e9e9ee; border-radius: 8px; margin-bottom: 8px; cursor: pointer; font-size: 14.5px;">
                            <input type="radio" name="member_id" value="<?= (int) $m['id'] ?>" required>
                            <?= htmlspecialchars($m['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="gate-btn">Continue</button>
            </form>
        <?php endif; ?>
    <?php
    committee_gate_page_end();
}
