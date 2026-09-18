<?php
require_once '../app/session_bootstrap.php';
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../path.php';

/**
 * Cycles, most recently archived first. A single archive action sets
 * archived_at = NOW() for every application in one UPDATE statement, so
 * all rows from the same cycle share the exact same archived_at value --
 * that's what groups them here, no separate "cycle" table needed.
 */
try {
    $cyclesStmt = $pdo->query("
        SELECT
            archived_at,
            COUNT(*) AS total,
            COUNT(*) FILTER (WHERE application_status = 'final_recipient') AS recipient_count,
            COUNT(*) FILTER (WHERE application_status = 'final_review') AS final_review_count,
            COUNT(*) FILTER (WHERE application_status = 'reviewed') AS reviewed_count,
            COUNT(*) FILTER (WHERE application_status = 'submitted') AS submitted_count,
            MIN(submitted_at) AS earliest_submitted,
            MAX(submitted_at) AS latest_submitted
        FROM scholarship_applications
        WHERE archived_at IS NOT NULL
        GROUP BY archived_at
        ORDER BY archived_at DESC
    ");
    $cycles = $cyclesStmt->fetchAll(PDO::FETCH_ASSOC);

    $recipientsStmt = $pdo->query("
        SELECT archived_at, id, first_name, last_name, intended_school
        FROM scholarship_applications
        WHERE archived_at IS NOT NULL AND application_status = 'final_recipient'
    ");
    $recipientsByCycle = [];
    foreach ($recipientsStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $recipientsByCycle[$r['archived_at']] = $r;
    }
} catch (Exception $e) {
    $cycles = [];
    $recipientsByCycle = [];
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
    <title>Archives - Morgan Legacy Scholarship</title>
    <style>
        .archive-search { padding: 8px 16px !important; border-radius: 20px !important; }
        .cycle-card { text-decoration: none; display: block; border: 1px solid rgb(241,242,243); border-radius: 14px; overflow: hidden; color: inherit; }
        .cycle-card:hover { border-color: #d8d8e0; }
        .cycle-avatar { width: 46px; height: 46px; border-radius: 50%; background: rgb(7,5,55); color: #C5A059; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 17px; flex-shrink: 0; }
        .cycle-badge { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; background: rgba(7,5,55,0.06); color: rgb(7,5,55); }
        .cycle-stat-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  <div class="case-accent"></div>

  <div style="padding: 28px 32px 20px;">
    <a href="<?= BASE_URL ?>/admin/" class="text-decoration-none" style="font-size: 13.5px; color: #9a9aa5; font-weight: 600;">
        <i class="bi bi-arrow-left me-1"></i> Back to application portal
    </a>

    <h3 class="mt-3 mb-1" style="font-weight: 700; font-size: 1.5rem; color: #212529;">Archives</h3>
    <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Past cycles, kept on file for historical record</h5>

    <div class="mt-3">
        <input type="text" id="cycleSearchInput" class="form-control form-control-sm archive-search"
               placeholder="Search archived cycles..." style="width: 300px;">
    </div>
  </div>

  <div style="padding: 0 32px 32px;">

    <?php if (!empty($_GET['cycle_error'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div><?= htmlspecialchars($_GET['cycle_error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php elseif (!empty($_GET['cycle_success'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= htmlspecialchars($_GET['cycle_success'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if (empty($cycles)): ?>
        <div class="text-center text-muted py-5">
            No archived cycles yet &mdash; a cycle appears here once you archive it from the dashboard.
        </div>
    <?php else: ?>
        <div id="cyclesList" class="d-flex flex-column gap-3">
            <?php foreach ($cycles as $i => $cycle): ?>
                <?php
                    $recipient = $recipientsByCycle[$cycle['archived_at']] ?? null;
                    $cycleYear = date('Y', strtotime($cycle['archived_at']));
                    $cycleLabel = "{$cycleYear} Cycle";
                    $searchText = strtolower($cycleLabel . ' ' . ($recipient ? $recipient['first_name'] . ' ' . $recipient['last_name'] . ' ' . $recipient['intended_school'] : ''));
                ?>
                <a href="archive_cycle.php?archived_at=<?= urlencode($cycle['archived_at']) ?>" class="cycle-card cycle-row" data-search="<?= htmlspecialchars($searchText) ?>">
                    <div style="padding: 20px 24px; display: flex; align-items: center; gap: 20px;">
                        <div class="cycle-avatar">
                            <?= $recipient ? htmlspecialchars(strtoupper(substr($recipient['first_name'], 0, 1) . substr($recipient['last_name'], 0, 1))) : '<i class="bi bi-archive"></i>' ?>
                        </div>

                        <div style="flex-grow: 1; min-width: 0;">
                            <div class="d-flex align-items-center gap-2">
                                <div style="font-size: 18px; font-weight: 800; color: #16151f;"><?= htmlspecialchars($cycleLabel) ?></div>
                                <?php if ($i === 0): ?>
                                    <span class="cycle-badge">Most recent</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 13.5px; color: #6c757d; margin-top: 3px;">
                                <?php if ($recipient): ?>
                                    Recipient: <strong style="color: #16151f;"><?= htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']) ?></strong> &bull; <?= htmlspecialchars($recipient['intended_school']) ?>
                                <?php else: ?>
                                    No final recipient was designated this cycle
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="text-align: right; flex-shrink: 0; padding-right: 4px;">
                            <div style="font-size: 20px; font-weight: 800; color: #16151f;"><?= (int) $cycle['total'] ?></div>
                            <div style="font-size: 11px; color: #9a9aa5; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Applicant<?= (int) $cycle['total'] === 1 ? '' : 's' ?></div>
                        </div>

                        <div style="text-align: right; flex-shrink: 0; width: 110px;">
                            <div style="font-size: 13px; font-weight: 600; color: #212529;"><?= date('M j, Y', strtotime($cycle['archived_at'])) ?></div>
                            <div style="font-size: 11px; color: #9a9aa5;">Archived</div>
                        </div>

                        <div style="color: #ced4da; font-size: 20px; flex-shrink: 0;"><i class="bi bi-chevron-right"></i></div>
                    </div>

                    <div style="padding: 12px 24px 16px; border-top: 1px solid #f3f3f6; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                        <?php if ((int) $cycle['recipient_count'] > 0): ?>
                            <div class="d-flex align-items-center gap-2" style="font-size: 12px; color: #6c757d;">
                                <span class="cycle-stat-dot" style="background: #C5A059;"></span> <?= (int) $cycle['recipient_count'] ?> Final Recipient
                            </div>
                        <?php endif; ?>
                        <?php if ((int) $cycle['final_review_count'] > 0): ?>
                            <div class="d-flex align-items-center gap-2" style="font-size: 12px; color: #6c757d;">
                                <span class="cycle-stat-dot" style="background: #198754;"></span> <?= (int) $cycle['final_review_count'] ?> reached Final Review
                            </div>
                        <?php endif; ?>
                        <?php if ((int) $cycle['reviewed_count'] > 0): ?>
                            <div class="d-flex align-items-center gap-2" style="font-size: 12px; color: #6c757d;">
                                <span class="cycle-stat-dot" style="background: #0d6efd;"></span> <?= (int) $cycle['reviewed_count'] ?> reached Reviewed
                            </div>
                        <?php endif; ?>
                        <?php if ((int) $cycle['submitted_count'] > 0): ?>
                            <div class="d-flex align-items-center gap-2" style="font-size: 12px; color: #6c757d;">
                                <span class="cycle-stat-dot" style="background: #9a9aa5;"></span> <?= (int) $cycle['submitted_count'] ?> still Submitted
                            </div>
                        <?php endif; ?>
                        <div class="ms-auto" style="font-size: 12px; color: #9a9aa5;">
                            <?php if ($cycle['earliest_submitted'] && $cycle['latest_submitted'] && date('Y-m-d', strtotime($cycle['earliest_submitted'])) !== date('Y-m-d', strtotime($cycle['latest_submitted']))): ?>
                                Submitted <?= date('M j', strtotime($cycle['earliest_submitted'])) ?> &ndash; <?= date('M j, Y', strtotime($cycle['latest_submitted'])) ?>
                            <?php else: ?>
                                Submitted <?= date('M j, Y', strtotime($cycle['latest_submitted'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

  </div>
</div>

</div>
</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('cycleSearchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#cyclesList .cycle-row').forEach(row => {
        row.style.display = row.dataset.search.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
