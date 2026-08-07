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
    <link rel="stylesheet" href="../assets/css/styles.css?v=11.2.0">
    <title>Committee Votes - Morgan Legacy Scholarship</title>
    <style>
        .tally-card { background: #fff; border: 1px solid rgb(241,242,243); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; }
        .tally-name { font-weight: 600; font-size: 14.5px; color: #212529; }
        .tally-count { background: rgb(7,5,55); color: #C5A059; font-weight: 700; font-size: 13px; padding: 4px 12px; border-radius: 20px; }
        .vote-table { width: 100%; border-collapse: collapse; }
        .vote-table th { text-align: left; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; padding: 12px 20px; border-bottom: 1px solid #f3f3f6; }
        .vote-table td { padding: 14px 20px; border-bottom: 1px solid #f6f6f8; vertical-align: middle; }
        .vote-table tr:last-child td { border-bottom: none; }
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
        <div class="row g-2 mb-4">
            <?php foreach ($tally as $t): ?>
                <div class="col-md-4">
                    <div class="tally-card">
                        <div class="tally-name"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                        <div class="tally-count"><?= (int) $t['vote_count'] ?> vote<?= ((int) $t['vote_count'] === 1) ? '' : 's' ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white" style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">
        <table class="vote-table">
            <thead>
                <tr>
                    <th>Committee Member</th>
                    <th>Their Pick</th>
                    <th>Voted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($memberVotes)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No committee members have been added yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($memberVotes as $mv): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold" style="font-size: 14.5px;"><?= htmlspecialchars($mv['member_name']) ?></div>
                                <div class="text-muted" style="font-size: 12.5px;"><?= htmlspecialchars($mv['member_email']) ?></div>
                            </td>
                            <td>
                                <?php if ($mv['picked_app_id']): ?>
                                    <a href="application_view.php?id=<?= (int) $mv['picked_app_id'] ?>" class="text-decoration-none" style="color: rgb(7,5,55); font-weight: 600;">
                                        <?= htmlspecialchars($mv['first_name'] . ' ' . $mv['last_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No pick yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 13.5px;">
                                <?= $mv['voted_at'] ? date('M j, Y g:i A', strtotime($mv['voted_at'])) : '&mdash;' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

  </div>
</div>

</div>
</main>

<?php require_once ROOT_PATH . '/assets/includes/footer.php'; ?>

</body>
</html>
