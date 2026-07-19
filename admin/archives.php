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

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.0.0">
    <title>Archives - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">


  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-4" style="padding: 15px 20px;">
        <div>
            <h3 class="mb-1" style="font-weight: 600; font-size: 1.5rem; color: #212529;">Archives</h3>
            <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Past cycles, kept on file for historical record</h5>
        </div>
    </div>

    <div class="mb-3">
        <input type="text" id="archiveSearchInput" class="form-control form-control-sm"
               placeholder="Search archived applicants..." style="width: 280px; padding-top: 8px !important; padding-bottom: 8px !important; border-radius: 20px !important;">
    </div>

    <div class="mt-2 bg-white shadow-sm"
     style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">

    <table class="table table-hover mb-0 align-middle" id="archivesTable">
        <thead class="table-light">
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
                <td colspan="7" class="text-center text-muted py-4">
                    No archived applications yet — applications will appear here once a cycle is archived from the dashboard.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($archivedApplications as $app): ?>
                <tr style="cursor: pointer;"
                    onclick="window.location.href='application_view.php?id=<?= $app['id'] ?>'">

                    <!-- Name + GPA -->
                    <td>
                        <div class="fw-semibold">
                            <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                        </div>
                        <div class="text-muted" style="font-size: 13px;">
                            GPA: <?= htmlspecialchars($app['gpa']) ?>
                        </div>
                    </td>

                    <!-- Contact -->
                    <td>
                        <div><?= htmlspecialchars($app['email']) ?></div>
                        <div class="text-muted" style="font-size: 13px;">
                            <?= htmlspecialchars($app['phone']) ?>
                        </div>
                    </td>

                    <!-- Intended School -->
                    <td>
                        <div><?= htmlspecialchars($app['intended_school']) ?></div>
                        <div class="text-muted" style="font-size: 13px;">
                            <?= htmlspecialchars($app['intended_major']) ?>
                        </div>
                    </td>

                    <!-- Date Submitted -->
                    <td>
                        <?= date('M j, Y', strtotime($app['submitted_at'])) ?>
                    </td>

                    <!-- Final Status -->
                    <td>
                        <span class="badge
                            <?php
                                echo match ($app['application_status']) {
                                    'submitted' => 'bg-primary-subtle text-primary',
                                    'reviewed'  => 'bg-secondary-subtle text-secondary',
                                    'final_review' => 'bg-success-subtle text-success',
                                    'final_recipient' => 'bg-info-subtle text-info',
                                    default => 'bg-light text-dark'
                                };
                            ?>">
                            <?= ucwords(str_replace('_', ' ', $app['application_status'])) ?>
                        </span>
                    </td>

                    <!-- Archived On -->
                    <td class="text-muted" style="font-size: 13px;">
                        <?= date('M j, Y', strtotime($app['archived_at'])) ?>
                    </td>

                    <!-- Chevron -->
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
    const rows = document.querySelectorAll('#archivesTable tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
