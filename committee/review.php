<?php
require_once '../app/db.php';
require_once '../path.php';

$token = $_GET['token'] ?? '';
require_once '../app/committee_access.php';
// Falls through only once the token and code are both verified.
// $committeeAccess (the active committee_access row) is now available.

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Final Review - Morgan Legacy Scholarship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body { background: rgb(249,250,251); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .top-bar { background: rgb(7,5,55); padding: 18px 0; margin-bottom: 28px; }
        .top-bar img { height: 40px; }
        .top-bar-title { color: #fff; font-weight: 700; font-size: 16px; margin-left: 12px; }
        .top-bar-sub { color: rgba(255,255,255,0.6); font-size: 12.5px; margin-left: 12px; }
        .case-card { background: #fff; border: 1px solid rgb(241,242,243); border-radius: 16px; overflow: hidden; }
        .page-head { padding: 24px 28px 4px; }
        .page-title { font-size: 22px; font-weight: 800; color: #16151f; }
        .page-sub { font-size: 14px; color: #6c757d; margin-top: 2px; }
        table.review-table { width: 100%; border-collapse: collapse; }
        table.review-table th { text-align: left; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; padding: 14px 28px; border-bottom: 1px solid #f3f3f6; }
        table.review-table td { padding: 16px 28px; border-bottom: 1px solid #f6f6f8; vertical-align: middle; }
        table.review-table tr:last-child td { border-bottom: none; }
        table.review-table tr:hover td { background: #fafbff; }
        .applicant-name { font-weight: 600; color: #212529; }
        .applicant-sub { font-size: 12.5px; color: #8a8a94; }
        .review-link { font-size: 13px; font-weight: 600; color: rgb(7,5,55); text-decoration: none; white-space: nowrap; }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="container d-flex align-items-center">
        <img src="/assets/images/logo.png" alt="Morgan Legacy Scholarship">
        <div>
            <div class="top-bar-title">Committee Review</div>
            <div class="top-bar-sub">Morgan Legacy Scholarship</div>
        </div>
    </div>
</div>

<div class="container pb-5" style="max-width: 1000px;">
    <div class="case-card">
        <div class="page-head">
            <div class="page-title">Applications in Final Review</div>
            <div class="page-sub">Click an applicant to view their full application. This list updates automatically as applications move through the process.</div>
        </div>

        <table class="review-table mt-3">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Intended School</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">No applications are currently in Final Review.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <tr style="cursor: pointer;" onclick="window.location.href='review_application.php?token=<?= urlencode($token) ?>&id=<?= (int) $app['id'] ?>'">
                        <td>
                            <div class="applicant-name"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                            <div class="applicant-sub">GPA: <?= htmlspecialchars($app['gpa']) ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($app['intended_school']) ?></div>
                            <div class="applicant-sub"><?= htmlspecialchars($app['intended_major']) ?></div>
                        </td>
                        <td><?= date('M j, Y', strtotime($app['submitted_at'])) ?></td>
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

</body>
</html>
