<?php
require_once '../app/db.php';
require_once '../path.php';

$token = $_GET['token'] ?? '';
require_once '../app/committee_access.php';
// Falls through only once token, code, and identity are all established.
// $committeeAccess, $committeeMemberId, $committeeMemberName are now available.

try {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, gpa, intended_school, intended_major, submitted_at
        FROM scholarship_applications
        WHERE application_status = 'final_review' AND archived_at IS NULL
        ORDER BY last_name, first_name
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $applications = [];
}

// This member's current pick, if any -- shown to them only, never to others.
$voteStmt = $pdo->prepare("SELECT application_id FROM committee_votes WHERE committee_member_id = :member_id");
$voteStmt->execute([':member_id' => $committeeMemberId]);
$myPickId = (int) $voteStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Final Review - Morgan Legacy Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css?v=11.2.0">
    <style>
        body { background: rgb(249,250,251); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .top-bar { background: rgb(7,5,55); padding: 18px 0; margin-bottom: 28px; }
        .top-bar img { height: 40px; }
        .top-bar-title { color: #fff; font-weight: 700; font-size: 16px; margin-left: 12px; }
        .top-bar-sub { color: rgba(255,255,255,0.6); font-size: 12.5px; margin-left: 12px; }
        .top-bar-who { color: rgba(255,255,255,0.85); font-size: 13px; }
        .top-bar-who a { color: rgba(255,255,255,0.6); text-decoration: underline; }
        .case-card { background: #fff; border: 1px solid rgb(241,242,243); border-radius: 16px; overflow: hidden; }
        .page-head { padding: 24px 28px 4px; }
        .page-title { font-size: 22px; font-weight: 800; color: #16151f; }
        .page-sub { font-size: 14px; color: #6c757d; margin-top: 2px; }
        .pick-banner { margin: 16px 28px 0; padding: 12px 16px; background: rgba(197,160,89,0.12); border-radius: 8px; font-size: 13.5px; color: #8a6d2e; display: flex; align-items: center; gap: 8px; }
        table.review-table { width: 100%; border-collapse: collapse; }
        table.review-table th { text-align: left; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; padding: 14px 28px; border-bottom: 1px solid #f3f3f6; }
        table.review-table td { padding: 16px 28px; border-bottom: 1px solid #f6f6f8; vertical-align: middle; }
        table.review-table tr:last-child td { border-bottom: none; }
        table.review-table tr:hover td { background: #fafbff; }
        table.review-table tr.is-my-pick td { background: rgba(197,160,89,0.06); }
        .applicant-name { font-weight: 600; color: #212529; }
        .applicant-sub { font-size: 12.5px; color: #8a8a94; }
        .review-link { font-size: 13px; font-weight: 600; color: rgb(7,5,55); text-decoration: none; white-space: nowrap; }
        .pick-btn { border: none; background: rgb(233,236,255); color: rgb(7,5,55); border-radius: 20px; padding: 6px 14px; font-size: 12.5px; font-weight: 600; white-space: nowrap; }
        .pick-btn.picked { background: #C5A059; color: #3a2f14; }
        .pick-btn:disabled { opacity: 0.6; }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center">
            <img src="/assets/images/logo.png" alt="Morgan Legacy Scholarship">
            <div>
                <div class="top-bar-title">Committee Review</div>
                <div class="top-bar-sub">Morgan Legacy Scholarship</div>
            </div>
        </div>
        <div class="top-bar-who">
            Reviewing as <strong><?= htmlspecialchars($committeeMemberName) ?></strong>
            &bull; <a href="?token=<?= urlencode($token) ?>&switch_identity=1">Not you?</a>
        </div>
    </div>
</div>

<div class="container pb-5" style="max-width: 1000px;">
    <div class="case-card">
        <div class="case-accent"></div>
        <div class="page-head">
            <div class="page-title">Applications in Final Review</div>
            <div class="page-sub">Click an applicant to view their full application, or use the star to mark your pick for final recipient.</div>
        </div>

        <?php if ($myPickId): ?>
            <div class="pick-banner">
                <i class="bi bi-star-fill"></i>
                <span>You've picked a candidate. You can change your mind anytime before a final recipient is announced.</span>
            </div>
        <?php endif; ?>

        <table class="review-table mt-3" id="reviewTable">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Intended School</th>
                    <th>Submitted</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">No applications are currently in Final Review.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <?php $isPicked = ((int) $app['id'] === $myPickId); ?>
                    <tr style="cursor: pointer;" class="<?= $isPicked ? 'is-my-pick' : '' ?>" onclick="window.location.href='review_application.php?token=<?= urlencode($token) ?>&id=<?= (int) $app['id'] ?>'">
                        <td>
                            <div class="applicant-name"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                            <div class="applicant-sub">GPA: <?= htmlspecialchars($app['gpa']) ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($app['intended_school']) ?></div>
                            <div class="applicant-sub"><?= htmlspecialchars($app['intended_major']) ?></div>
                        </td>
                        <td><?= date('M j, Y', strtotime($app['submitted_at'])) ?></td>
                        <td onclick="event.stopPropagation()">
                            <button type="button" class="pick-btn <?= $isPicked ? 'picked' : '' ?>" data-app-id="<?= (int) $app['id'] ?>" onclick="castVote(this)">
                                <i class="bi <?= $isPicked ? 'bi-star-fill' : 'bi-star' ?> me-1"></i><?= $isPicked ? 'My Pick' : 'Pick' ?>
                            </button>
                        </td>
                        <td class="text-end">
                            <a href="review_application.php?token=<?= urlencode($token) ?>&id=<?= (int) $app['id'] ?>" class="review-link" onclick="event.stopPropagation()">
                                View <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once ROOT_PATH . '/assets/includes/footer.php'; ?>

<script>
function castVote(btn) {
    const appId = btn.dataset.appId;
    btn.disabled = true;

    fetch('vote.php?token=<?= urlencode($token) ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ application_id: appId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            btn.disabled = false;
            alert(data.message || 'Something went wrong recording your pick.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        alert('Something went wrong recording your pick. Please try again.');
    });
}
</script>

</body>
</html>
