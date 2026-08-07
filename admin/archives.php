<?php
session_start();
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../path.php';

/**
 * Archived applications, most recently archived first
 */
try {
    $archivedStmt = $pdo->query("
        SELECT
            id,
            first_name,
            last_name,
            gpa,
            email,
            phone,
            intended_school,
            intended_major,
            application_status,
            submitted_at,
            archived_at
        FROM scholarship_applications
        WHERE archived_at IS NOT NULL
        ORDER BY archived_at DESC, submitted_at DESC
    ");

    $archivedApplications = $archivedStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $archivedApplications = [];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.2.0">
    <title>Archives - Morgan Legacy Scholarship</title>
    <style>
        .archive-table { width: 100%; border-collapse: collapse; }
        .archive-table th { text-align: left; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; padding: 14px 20px; border-bottom: 1px solid #f3f3f6; background: rgb(249,250,251); }
        .archive-table td { padding: 14px 20px; border-bottom: 1px solid #f6f6f8; vertical-align: middle; }
        .archive-table tr:last-child td { border-bottom: none; }
        .archive-table tr.archive-row:hover td { background: #fafbff; }
        .archive-avatar { width: 38px; height: 38px; border-radius: 50%; background: rgb(7,5,55); color: #C5A059; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .applicant-name { font-weight: 600; font-size: 14.5px; color: #212529; }
        .applicant-sub { font-size: 12.5px; color: #8a8a94; }
        .status-pill { display: inline-block; white-space: nowrap; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: capitalize; }
        .status-pill.submitted { background: rgba(108,117,125,0.12); color: #6c757d; }
        .status-pill.reviewed { background: rgba(13,110,253,0.1); color: #0d6efd; }
        .status-pill.final_review { background: rgba(25,135,84,0.12); color: #198754; }
        .status-pill.final_recipient { background: rgba(197,160,89,0.16); color: #8a6d2e; }
        .archive-search { padding: 8px 16px !important; border-radius: 20px !important; }
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
  </div>

  <div style="padding: 0 32px 32px;">

    <div class="mb-3">
        <input type="text" id="archiveSearchInput" class="form-control form-control-sm archive-search"
               placeholder="Search archived applicants..." style="width: 280px;">
    </div>

    <div class="bg-white" style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">
        <table class="archive-table" id="archivesTable">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Contact</th>
                    <th>Intended School</th>
                    <th>Submitted</th>
                    <th>Final Status</th>
                    <th>Archived</th>
                    <th style="width: 40px;"></th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($archivedApplications)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        No archived applications yet &mdash; applications will appear here once a cycle is archived from the dashboard.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($archivedApplications as $app): ?>
                    <tr class="archive-row" style="cursor: pointer;"
                        onclick="window.location.href='application_view.php?id=<?= $app['id'] ?>'">

                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="archive-avatar"><?= htmlspecialchars(strtoupper(substr($app['first_name'], 0, 1))) ?></div>
                                <div>
                                    <div class="applicant-name"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                                    <div class="applicant-sub">GPA: <?= htmlspecialchars($app['gpa']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div><?= htmlspecialchars($app['email']) ?></div>
                            <div class="applicant-sub"><?= htmlspecialchars($app['phone']) ?></div>
                        </td>

                        <td>
                            <div><?= htmlspecialchars($app['intended_school']) ?></div>
                            <div class="applicant-sub"><?= htmlspecialchars($app['intended_major']) ?></div>
                        </td>

                        <td><?= date('M j, Y', strtotime($app['submitted_at'])) ?></td>

                        <td>
                            <span class="status-pill <?= htmlspecialchars($app['application_status']) ?>">
                                <?= ucwords(str_replace('_', ' ', $app['application_status'])) ?>
                            </span>
                        </td>

                        <td class="applicant-sub"><?= date('M j, Y', strtotime($app['archived_at'])) ?></td>

                        <td class="text-end text-muted">
                            <i class="bi bi-chevron-right"></i>
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

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('archiveSearchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#archivesTable tbody tr.archive-row');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
