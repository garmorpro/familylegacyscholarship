<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../path.php';

try {
    $membersStmt = $pdo->query("
        SELECT
            cm.id AS member_id,
            cm.name AS member_name,
            cm.email AS member_email,
            sa.id AS picked_app_id,
            sa.first_name,
            sa.last_name,
            cv.updated_at AS voted_at
        FROM committee_members cm
        LEFT JOIN committee_votes cv ON cv.committee_member_id = cm.id
        LEFT JOIN scholarship_applications sa ON sa.id = cv.application_id
        ORDER BY cm.name
    ");
    $memberVotes = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

    $tallyStmt = $pdo->query("
        SELECT sa.id, sa.first_name, sa.last_name, COUNT(cv.id) AS vote_count
        FROM committee_votes cv
        JOIN scholarship_applications sa ON sa.id = cv.application_id
        GROUP BY sa.id, sa.first_name, sa.last_name
        ORDER BY vote_count DESC, sa.last_name
    ");
    $tally = $tallyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $memberVotes = [];
    $tally = [];
}

// Each distinct picked candidate gets a color from a small rotating
// palette (not one color per member) so who-picked-whom reads at a
// glance without hardcoding a color per applicant.
$pickPalette = [
    ['bg' => 'rgba(197,160,89,0.16)', 'color' => '#8a6d2e'],
    ['bg' => 'rgba(7,5,55,0.08)',     'color' => 'rgb(7,5,55)'],
    ['bg' => 'rgba(25,135,84,0.12)',  'color' => '#198754'],
    ['bg' => 'rgba(13,110,253,0.12)', 'color' => '#0d6efd'],
];
$candidateColors = [];
foreach ($memberVotes as $mv) {
    if ($mv['picked_app_id'] && !isset($candidateColors[$mv['picked_app_id']])) {
        $candidateColors[$mv['picked_app_id']] = $pickPalette[count($candidateColors) % count($pickPalette)];
    }
}

function member_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials !== '' ? $initials : '?';
}

$totalMembers = count($memberVotes);
$votedMembers = count(array_filter($memberVotes, fn($mv) => (bool) $mv['picked_app_id']));
$maxVotes = 0;
foreach ($tally as $t) {
    $maxVotes = max($maxVotes, (int) $t['vote_count']);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?= time() ?>">
    <title>Committee Votes - Morgan Legacy Scholarship</title>
    <style>
        .standings-label { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9a9aa5; margin-bottom: 14px; }
        .standings-row { display: flex; align-items: center; gap: 16px; }
        .standings-name { width: 170px; font-size: 15px; font-weight: 700; color: #16151f; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .standings-track { flex-grow: 1; height: 26px; background: rgb(241,242,243); border-radius: 13px; overflow: hidden; }
        .standings-fill { height: 100%; background: linear-gradient(90deg, #C5A059, #d9b876); border-radius: 13px; }
        .standings-count { width: 80px; text-align: right; font-size: 14.5px; font-weight: 700; color: rgb(7,5,55); flex-shrink: 0; }
        .ballot-row { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border: 1px solid #f0f0f3; border-radius: 12px; }
        .ballot-row.has-pick { background: rgba(197,160,89,0.04); }
        .ballot-avatar { width: 38px; height: 38px; border-radius: 50%; background: rgb(233,236,255); color: rgb(7,5,55); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
        .ballot-pick-pill { text-decoration: none; display: inline-block; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
        .ballot-voted-at { width: 130px; text-align: right; font-size: 12px; color: #9a9aa5; flex-shrink: 0; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>

<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; border-color: rgb(241,242,243) !important; padding: 0 !important;">
  <div class="case-accent"></div>

  <div style="padding: 28px 32px 24px;">
    <a href="index.php" class="text-decoration-none" style="font-size: 13.5px; color: #9a9aa5; font-weight: 600;">
        <i class="bi bi-arrow-left me-1"></i> Back to applications
    </a>
    <h2 class="fw-semibold mt-3 mb-1">Committee Votes</h2>
    <div class="text-muted">Who each committee member picked as their final-recipient candidate.</div>
  </div>

  <div style="padding: 0 32px 32px;">

    <?php if (empty($tally)): ?>
        <div class="text-muted mb-4" style="font-size: 14px;">No votes have been cast yet.</div>
    <?php else: ?>
        <div class="mb-4">
            <div class="standings-label">
                <?= $votedMembers ?> of <?= $totalMembers ?> member<?= $totalMembers === 1 ? '' : 's' ?> <?= $votedMembers === 1 ? 'has' : 'have' ?> voted
            </div>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($tally as $t): ?>
                    <?php $pct = $maxVotes > 0 ? round(((int) $t['vote_count'] / $maxVotes) * 100) : 0; ?>
                    <div class="standings-row">
                        <div class="standings-name"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                        <div class="standings-track">
                            <div class="standings-fill" style="width: <?= $pct ?>%;"></div>
                        </div>
                        <div class="standings-count"><?= (int) $t['vote_count'] ?> vote<?= ((int) $t['vote_count'] === 1) ? '' : 's' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <hr style="border-color: rgb(241,242,243); margin: 0 0 24px;">

    <div class="standings-label">Committee Members</div>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php if (empty($memberVotes)): ?>
            <div class="text-center text-muted py-4">No committee members have been added yet.</div>
        <?php else: ?>
            <?php foreach ($memberVotes as $mv): ?>
                <?php
                    $hasPick = (bool) $mv['picked_app_id'];
                    $pickColor = $hasPick ? ($candidateColors[$mv['picked_app_id']] ?? $pickPalette[0]) : null;
                ?>
                <div class="ballot-row <?= $hasPick ? 'has-pick' : '' ?>">
                    <div class="ballot-avatar"><?= htmlspecialchars(member_initials($mv['member_name'])) ?></div>
                    <div style="flex-grow: 1;">
                        <div class="fw-semibold" style="font-size: 14.5px;"><?= htmlspecialchars($mv['member_name']) ?></div>
                        <div class="text-muted" style="font-size: 12.5px;"><?= htmlspecialchars($mv['member_email']) ?></div>
                    </div>
                    <?php if ($hasPick): ?>
                        <a href="application_view.php?id=<?= (int) $mv['picked_app_id'] ?>" class="ballot-pick-pill" style="background: <?= $pickColor['bg'] ?>; color: <?= $pickColor['color'] ?>;">
                            <?= htmlspecialchars($mv['first_name'] . ' ' . $mv['last_name']) ?>
                        </a>
                        <div class="ballot-voted-at"><?= $mv['voted_at'] ? date('M j, g:i A', strtotime($mv['voted_at'])) : '&mdash;' ?></div>
                    <?php else: ?>
                        <div style="font-size: 13px; color: #ced4da; font-style: italic;">No pick yet</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

  </div>
</div>

</div>
</main>

<?php require_once ROOT_PATH . '/assets/includes/footer.php'; ?>

</body>
</html>
