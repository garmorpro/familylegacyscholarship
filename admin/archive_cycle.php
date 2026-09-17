<?php
session_start();
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../path.php';

// archived_at is the exact timestamp a whole cycle was archived under (see
// archives.php) -- used here as-is, unreformatted, as the identifier for
// which cycle to load, since every row in that cycle shares this exact value.
$archivedAt = $_GET['archived_at'] ?? '';

$cycleApplications = [];
if ($archivedAt !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, gpa, email, phone, intended_school, intended_major,
                   application_status, submitted_at
            FROM scholarship_applications
            WHERE archived_at = :archived_at
            ORDER BY
                CASE application_status
                    WHEN 'final_recipient' THEN 1
                    WHEN 'final_review' THEN 2
                    WHEN 'reviewed' THEN 3
                    ELSE 4
                END,
                last_name, first_name
        ");
        $stmt->execute([':archived_at' => $archivedAt]);
        $cycleApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $cycleApplications = [];
    }
}

$recipient = null;
$roster = [];
foreach ($cycleApplications as $app) {
    if ($app['application_status'] === 'final_recipient') {
        $recipient = $app;
    } else {
        $roster[] = $app;
    }
}

$cycleYear = $cycleApplications ? date('Y', strtotime($archivedAt)) : null;
$cycleLabel = $cycleYear ? "{$cycleYear} Cycle" : null;
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
    <title><?= $cycleLabel ? htmlspecialchars($cycleLabel) : 'Cycle' ?> - Archives - Morgan Legacy Scholarship</title>
    <style>
        .archive-search { padding: 8px 16px !important; border-radius: 20px !important; }
        .recipient-hero { border: 1.5px solid rgba(197,160,89,0.4); border-radius: 14px; padding: 22px 26px; display: flex; align-items: center; gap: 20px; background: rgba(197,160,89,0.06); }
        .roster-avatar { width: 36px; height: 36px; border-radius: 50%; background: rgb(233,236,255); color: rgb(7,5,55); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
        .roster-row { display: flex; align-items: center; gap: 14px; padding: 13px 16px; border: 1px solid #f0f0f3; border-radius: 12px; text-decoration: none; color: inherit; }
        .roster-row:hover { border-color: #d8d8e0; }
        .status-pill { display: inline-block; white-space: nowrap; font-size: 11.5px; font-weight: 700; padding: 3px 11px; border-radius: 20px; text-transform: capitalize; flex-shrink: 0; }
        .status-pill.submitted { background: rgba(108,117,125,0.12); color: #6c757d; }
        .status-pill.reviewed { background: rgba(13,110,253,0.12); color: #0d6efd; }
        .status-pill.final_review { background: rgba(25,135,84,0.12); color: #198754; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  <div class="case-accent"></div>

<?php if (empty($cycleApplications)): ?>

    <div style="padding: 28px 32px;">
        <a href="archives.php" class="text-decoration-none" style="font-size: 13.5px; color: #9a9aa5; font-weight: 600;">
            <i class="bi bi-arrow-left me-1"></i> Back to Archives
        </a>
        <div class="text-center text-muted py-5">
            This cycle couldn't be found &mdash; it may have been archived under a different link.
        </div>
    </div>

<?php else: ?>

  <div style="padding: 28px 32px 20px;">
    <a href="archives.php" class="text-decoration-none" style="font-size: 13.5px; color: #9a9aa5; font-weight: 600;">
        <i class="bi bi-arrow-left me-1"></i> Back to Archives
    </a>

    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mt-3">
        <div>
            <h3 class="mb-1" style="font-weight: 700; font-size: 1.5rem; color: #212529;"><?= htmlspecialchars($cycleLabel) ?></h3>
            <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">
                Archived <?= date('M j, Y', strtotime($archivedAt)) ?> &bull; <?= count($cycleApplications) ?> applicant<?= count($cycleApplications) === 1 ? '' : 's' ?> archived
            </h5>
        </div>
        <input type="text" id="rosterSearchInput" class="form-control form-control-sm archive-search"
               placeholder="Search this cycle..." style="width: 260px;">
    </div>
  </div>

    <?php if ($recipient): ?>
    <div style="padding: 0 32px 8px;">
        <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9a9aa5; margin-bottom: 12px;">Final Recipient</div>

        <div class="recipient-hero">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(7,5,55); color: #C5A059; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 22px; flex-shrink: 0;">
                <?= htmlspecialchars(strtoupper(substr($recipient['first_name'], 0, 1) . substr($recipient['last_name'], 0, 1))) ?>
            </div>
            <div style="flex-grow: 1;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #8a6d2e; margin-bottom: 3px;"><i class="bi bi-star-fill me-1"></i>Final Recipient</div>
                <div style="font-size: 21px; font-weight: 800; color: #16151f;"><?= htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']) ?></div>
                <div style="font-size: 13.5px; color: #6c757d; margin-top: 2px;">
                    <?= htmlspecialchars($recipient['intended_school']) ?> &bull; <?= htmlspecialchars($recipient['intended_major']) ?> &bull; GPA <?= htmlspecialchars($recipient['gpa']) ?>
                </div>
            </div>
            <div style="text-align: right; flex-shrink: 0;">
                <div style="font-size: 13px; font-weight: 600; color: #212529;"><?= date('M j, Y', strtotime($recipient['submitted_at'])) ?></div>
                <div style="font-size: 11px; color: #9a9aa5;">Submitted</div>
            </div>
            <a href="application_view.php?id=<?= (int) $recipient['id'] ?>" style="text-decoration: none; font-size: 13px; font-weight: 700; color: rgb(7,5,55); background: #fff; border: 1px solid #e2e2e8; border-radius: 8px; padding: 9px 16px; flex-shrink: 0;">View Application</a>
        </div>
    </div>
    <?php endif; ?>

    <div style="padding: <?= $recipient ? '20px' : '0' ?> 32px 32px;">
        <?php if (!empty($roster)): ?>
            <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9a9aa5; margin-bottom: 14px;">
                <?= $recipient ? 'Also Archived This Cycle' : 'Archived Applicants' ?>
            </div>
        <?php endif; ?>

        <div id="rosterList" class="d-flex flex-column gap-2">
            <?php foreach ($roster as $app): ?>
                <?php $searchText = strtolower($app['first_name'] . ' ' . $app['last_name'] . ' ' . $app['intended_school'] . ' ' . $app['intended_major']); ?>
                <a href="application_view.php?id=<?= (int) $app['id'] ?>" class="roster-row roster-item" data-search="<?= htmlspecialchars($searchText) ?>">
                    <div class="roster-avatar"><?= htmlspecialchars(strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1))) ?></div>
                    <div style="flex-grow: 1;">
                        <div style="font-size: 14px; font-weight: 600; color: #212529;"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                        <div style="font-size: 12.5px; color: #9a9aa5;"><?= htmlspecialchars($app['intended_school']) ?> &bull; <?= htmlspecialchars($app['intended_major']) ?> &bull; GPA <?= htmlspecialchars($app['gpa']) ?></div>
                    </div>
                    <div style="width: 100px; text-align: right; font-size: 12px; color: #9a9aa5; flex-shrink: 0;"><?= date('M j, Y', strtotime($app['submitted_at'])) ?></div>
                    <span class="status-pill <?= htmlspecialchars($app['application_status']) ?>">
                        <?= ucwords(str_replace('_', ' ', $app['application_status'])) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

<?php endif; ?>

</div>

</div>
</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
const rosterSearchInput = document.getElementById('rosterSearchInput');
if (rosterSearchInput) {
    rosterSearchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('#rosterList .roster-item').forEach(row => {
            row.style.display = row.dataset.search.includes(filter) ? '' : 'none';
        });
    });
}
</script>

</body>
</html>
