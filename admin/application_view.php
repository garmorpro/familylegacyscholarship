<?php
require_once '../app/db.php';
require_once '../path.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

/**
 * Status counts + total
 */
try {
    $countsStmt = $pdo->query("
        SELECT application_status, COUNT(*) AS total
        FROM scholarship_applications
        GROUP BY application_status
    ");

    $statusCounts = [
        'submitted' => 0,
        'reviewed'  => 0,
        'final_review'  => 0
    ];

    while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($statusCounts[$row['application_status']])) {
            $statusCounts[$row['application_status']] = (int) $row['total'];
        }
    }

    // Total applications (all statuses)
    $totalApplications = array_sum($statusCounts);

} catch (Exception $e) {
    $statusCounts = [
        'submitted' => 0,
        'reviewed'  => 0,
        'final_review'  => 0
    ];
    $totalApplications = 0;
}

/**
 * Fetch applications for table
 */
try {
    $applicationsStmt = $pdo->query("
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
            submitted_at
        FROM scholarship_applications
        ORDER BY id DESC
    ");

    $applications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $applications = [];
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.1.0">
    <title>Application Portal - Morgan Legacy Scholarship</title>
    <style>
        /* Case card chrome */
        .case-accent { height: 5px; background: linear-gradient(90deg, rgb(7,5,55), #C5A059); }
        .back-link { font-size: 13.5px; color: #9a9aa5; font-weight: 600; }
        .back-link:hover { color: rgb(7,5,55); }
        .meta-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9a9aa5; }
        .meta-value { font-size: 14px; font-weight: 700; color: #212529; }
        .meta-value.success { color: #198754; }
        .meta-value.secondary { color: #6c757d; }
        .meta-value.danger { color: #dc3545; }

        /* Application status: connected pill chips */
        .stage-panel { background: rgb(249,250,251); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 0; margin-top: 4px; }
        .stage-chip { display: flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #fff; border: 1.5px solid #e2e2e8; color: #9a9aa5; white-space: nowrap; }
        .stage-chip.done { background: rgb(7,5,55); border-color: rgb(7,5,55); color: #fff; }
        .stage-chip.current { background: #fff; border-color: rgb(7,5,55); color: rgb(7,5,55); }
        .stage-chip.complete { background: #C5A059; border-color: #C5A059; color: #3a2f14; }
        .stage-connector { width: 22px; height: 2px; background: #e2e2e8; flex-shrink: 0; }
        .stage-connector.done { background: rgb(7,5,55); }

        .btn-stage-cta { border:none; padding: 11px 26px; border-radius:8px; font-weight:600; font-size:14.5px; }
        .btn-stage-cta.review { background: rgb(233,236,255); color: rgb(7,5,55); }
        .btn-stage-cta.review:hover { background: rgb(220,224,255); }
        .btn-stage-cta.final { background: rgba(197,160,89,0.18); color: #8a6d2e; }
        .btn-stage-cta.final:hover { background: rgba(197,160,89,0.3); }
        .btn-stage-cta.recipient { background: rgb(7,5,55); color:#fff; }
        .btn-stage-cta.recipient:hover { background: rgb(20,16,80); }

        /* Application detail: sidebar + document layout, flowing on one flat surface */
        .sidebar-col { border-right: 1px solid #f3f3f6; padding-right: 28px; position: sticky; top: 20px; align-self: flex-start; }
        .detail-sidebar-block { margin-bottom: 22px; }
        .detail-sidebar-block:last-child { margin-bottom: 0; }
        .detail-sidebar-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9a9aa5; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .detail-sidebar-label i { color: #C5A059; }
        .detail-sidebar-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 13.5px; padding: 6px 0; border-bottom: 1px solid #f3f3f6; }
        .detail-sidebar-row:last-child { border-bottom: none; }
        .detail-sidebar-row .k { color: #6c757d; }
        .detail-sidebar-row .v { font-weight: 600; color: #212529; text-align: right; word-break: break-word; }
        .detail-sidebar-row .v a { color: rgb(7,5,55); }
        .detail-status-pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .detail-status-pill.completed { background: rgba(25,135,84,0.12); color: #198754; }
        .detail-status-pill.sent { background: rgba(13,110,253,0.12); color: #0d6efd; }
        .detail-status-pill.not-sent { background: rgba(108,117,125,0.12); color: #6c757d; }
        .detail-sidebar-action { font-size: 12.5px; font-weight: 600; text-decoration: none; }

        .detail-doc-section { padding: 22px 0; border-bottom: 1px solid #f3f3f6; }
        .detail-doc-section:first-child { padding-top: 4px; }
        .detail-doc-section:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-doc-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .detail-doc-head i { color: #C5A059; font-size: 16px; }
        .detail-doc-title { font-weight: 700; font-size: 15.5px; color: #212529; }
        .detail-doc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .detail-doc-label { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; margin-bottom: 4px; }
        .detail-doc-value { font-size: 14.5px; color: #333; }
        .detail-word-count { font-size: 12px; color: #9a9aa5; font-weight: 600; margin-left: auto; }
        .detail-essay-box { background: #FBF9F3; border: 1px solid #ECE6D8; border-radius: 10px; padding: 16px 18px; font-size: 14.5px; line-height: 1.7; color: #2b2b2b; margin-top: 4px; }

        @media (max-width: 991px) {
            .sidebar-col { position: static; border-right: none; border-bottom: 1px solid #f3f3f6; padding-right: 0; padding-bottom: 20px; margin-bottom: 20px; }
            .detail-doc-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; border-color: rgb(241,242,243) !important; padding: 0 !important;">
  <div class="case-accent"></div>

  <div style="padding: 28px 32px 24px;">
    <!-- Back link -->
    <a href="<?= BASE_URL ?>/admin/" class="text-decoration-none back-link d-inline-flex align-items-center mb-3">
        <i class="bi bi-arrow-left me-1"></i> Back to applications
    </a>

<?php
// Get the application ID
$appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE id = ?");
    $stmt->execute([$appId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $application = null;
}

// Recommendation status, used to inform (not block) review actions below
$recStatusStmt = $pdo->prepare("SELECT status FROM recommendations WHERE scholarship_application_id = :id LIMIT 1");
$recStatusStmt->execute([':id' => $appId]);
$recommendationStatus = $recStatusStmt->fetchColumn(); // false | 'not_sent' | 'sent' | 'completed'
$recommendationReceived = ($recommendationStatus === 'completed');

// Archived applications are read-only history — no status-changing actions
$isArchived = $application && !empty($application['archived_at']);
?>

<?php if ($application): ?>

<div class="row align-items-start">
    <!-- Left: Name + Major/School -->
    <div class="col-md-6">
        <div style="font-size: 26px; font-weight: 800; color: #16151f; letter-spacing: -0.01em; margin-bottom: 2px;">
            <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
            <?php if ($isArchived): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-2" style="font-size: 12px; vertical-align: middle;">
                    <i class="bi bi-archive me-1"></i>Archived
                </span>
            <?php endif; ?>
        </div>
        <div style="font-size: 14.5px; color: #6c757d; font-weight: 500;">
            <?= htmlspecialchars($application['intended_major']) ?> &bull; <?= htmlspecialchars($application['intended_school']) ?>
        </div>
        <?php if ($isArchived): ?>
            <div class="text-muted mt-1" style="font-size: 13px;">
                Archived <?= date('M j, Y', strtotime($application['archived_at'])) ?> — this record is read-only.
            </div>
        <?php endif; ?>
    </div>

    <!-- Right: Submission Date & Recommendation -->
<div class="col-md-6 text-md-end mt-3 mt-md-0">

    <?php if (in_array($application['application_status'], ['submitted', 'reviewed', 'final_review'], true)): ?>
        <div class="mb-2">
            <span class="meta-label">Recommendation&nbsp; </span>
            <?php if ($recommendationStatus === 'completed'): ?>
                <span class="meta-value success">Received</span>
            <?php elseif ($recommendationStatus === 'sent'): ?>
                <span class="meta-value secondary">Sent, awaiting response</span>
            <?php else: ?>
                <span class="meta-value danger">Not sent yet</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div>
        <span class="meta-label">Submitted&nbsp; </span>
        <span class="meta-value"><?= date('M j, Y', strtotime($application['submitted_at'])) ?></span>
    </div>
</div>

</div>

<?php
// Stage pipeline -- drives the progress stepper and the single contextual
// action button below it, replacing the old set of separate stage buttons.
$stageOrder = ['submitted', 'reviewed', 'final_review', 'final_recipient'];
$stageLabels = ['Submitted', 'Reviewed', 'Final Review', 'Recipient'];
$stageIdx = array_search($application['application_status'], $stageOrder, true);

// Check if any *active* (non-archived) application has already been
// selected as final recipient — archived recipients from past cycles
// don't count, or a new cycle could never designate a recipient again.
$stmt = $pdo->query("SELECT COUNT(*) FROM scholarship_applications WHERE application_status = 'final_recipient' AND archived_at IS NULL");
$finalCount = (int) $stmt->fetchColumn();

// Final review has an admin-configurable cap (Settings > Review Limits).
$limitValue = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'final_review_limit'")->fetchColumn();
$finalReviewLimit = ($limitValue !== false && ctype_digit((string) $limitValue)) ? (int) $limitValue : 10;
$finalReviewCount = (int) $pdo->query("SELECT COUNT(*) FROM scholarship_applications WHERE application_status = 'final_review' AND archived_at IS NULL")->fetchColumn();
$finalReviewAtCapacity = $finalReviewCount >= $finalReviewLimit;
?>

<div class="pb-3">
    <div class="stage-panel">
        <?php foreach ($stageOrder as $i => $stage): ?>
            <?php if ($i > 0): ?>
                <div class="stage-connector <?= ($stageIdx !== false && $i <= $stageIdx) ? 'done' : '' ?>"></div>
            <?php endif; ?>
            <?php
                $isLastStage = $i === count($stageOrder) - 1;
                if ($stageIdx !== false && $i < $stageIdx) {
                    $chipClass = 'done';
                } elseif ($stageIdx !== false && $i === $stageIdx) {
                    $chipClass = $isLastStage ? 'complete' : 'current';
                } else {
                    $chipClass = 'pending';
                }
            ?>
            <div class="stage-chip <?= $chipClass ?>">
                <?php if ($chipClass === 'done'): ?>
                    <i class="bi bi-check-lg"></i>
                <?php elseif ($chipClass === 'current'): ?>
                    <i class="bi bi-hourglass-split"></i>
                <?php elseif ($chipClass === 'complete'): ?>
                    <i class="bi bi-trophy-fill"></i>
                <?php endif; ?>
                <?= $stageLabels[$i] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isArchived): ?>
        <div class="text-center text-muted mt-3" style="font-size: 13.5px;">
            <i class="bi bi-lock-fill me-1"></i>This record is archived and read-only.
        </div>
    <?php elseif ($application['application_status'] === 'submitted'): ?>
        <div class="text-center mt-2">
            <form method="POST" action="mark_reviewed.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $application['id'] ?>">
                <button type="submit" class="btn-stage-cta review">Mark as Reviewed</button>
            </form>
        </div>
    <?php elseif ($application['application_status'] === 'reviewed'): ?>
        <?php if ($finalReviewAtCapacity): ?>
            <div class="text-center text-muted mt-3" style="font-size: 13.5px;">
                Final review is full (<?= $finalReviewCount ?>/<?= $finalReviewLimit ?>) for this cycle.
            </div>
        <?php else: ?>
            <div class="text-center mt-2">
                <form method="POST" action="mark_final_review.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $application['id'] ?>">
                    <button type="submit" class="btn-stage-cta final">Advance to Final Review</button>
                </form>
            </div>
        <?php endif; ?>
    <?php elseif ($application['application_status'] === 'final_review'): ?>
        <?php if ($finalCount === 0): ?>
            <div class="text-center mt-2">
                <form method="POST" action="mark_final_selected.php" id="designateForm" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $application['id'] ?>">
                    <button type="submit" class="btn-stage-cta recipient">
                        <i class="bi bi-star-fill me-1"></i>Designate as Final Recipient
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="text-center text-muted mt-3" style="font-size: 13.5px;">
                A final recipient has already been selected for this cycle.
            </div>
        <?php endif; ?>
    <?php elseif ($application['application_status'] === 'final_recipient'): ?>
        <div class="text-center mt-3" style="font-size: 13.5px; color: #8a6d2e; font-weight: 600;">
            Selected as this cycle's final recipient
        </div>
    <?php endif; ?>
</div>

  </div>

  <div style="padding: 26px 32px 32px; border-top: 1px solid #f3f3f6;">

<div class="row">

    <!-- Sidebar: reference info -->
    <div class="col-lg-3 sidebar-col">

            <div class="detail-sidebar-block">
                <div class="detail-sidebar-label"><i class="bi bi-envelope-fill"></i>Contact</div>
                <div class="detail-sidebar-row">
                    <span class="k">Email</span>
                    <span class="v"><a href="mailto:<?= htmlspecialchars($application['email'] ?? 'N/A') ?>"><?= htmlspecialchars($application['email'] ?? 'N/A') ?></a></span>
                </div>
                <div class="detail-sidebar-row">
                    <span class="k">Phone</span>
                    <span class="v"><?= htmlspecialchars($application['phone'] ?? 'N/A') ?></span>
                </div>
            </div>

            <div class="detail-sidebar-block">
                <div class="detail-sidebar-label"><i class="bi bi-mortarboard-fill"></i>Academic</div>
                <div class="detail-sidebar-row">
                    <span class="k">GPA</span>
                    <span class="v"><?= htmlspecialchars($application['gpa'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-sidebar-row">
                    <span class="k">Grad Year</span>
                    <span class="v"><?= htmlspecialchars($application['expected_graduation_year'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-sidebar-row">
                    <span class="k">Institution</span>
                    <span class="v"><?= htmlspecialchars($application['institution_type'] ?? 'N/A') ?></span>
                </div>
            </div>

            <?php
            // Fetch recommendation for this application
            $recommendationStmt = $pdo->prepare("
                SELECT
                    r.id,
                    r.recommender_name,
                    r.recommender_email,
                    r.recommender_relationship,
                    r.status AS recommender_status,
                    r.recommendation,
                    r.completed_date,
                    s.first_name,
                    s.last_name
                FROM recommendations r
                JOIN scholarship_applications s ON s.id = r.scholarship_application_id
                WHERE r.scholarship_application_id = :app_id
                LIMIT 1
            ");
            $recommendationStmt->execute([':app_id' => $application['id']]);
            $recommendation = $recommendationStmt->fetch(PDO::FETCH_ASSOC);

            // Combine first and last name for easier use
            $recommendation['applicant_name'] = $recommendation['first_name'] . ' ' . $recommendation['last_name'];

            $recStatus = strtolower($recommendation['recommender_status'] ?? 'not_sent');
            switch ($recStatus) {
                case 'completed': $recPillClass = 'completed'; $recPillText = 'Completed'; break;
                case 'sent':      $recPillClass = 'sent';      $recPillText = 'Sent';      break;
                default:          $recPillClass = 'not-sent';  $recPillText = 'Not Sent';
            }
            ?>

            <div class="detail-sidebar-block">
                <div class="detail-sidebar-label"><i class="bi bi-file-earmark-person-fill"></i>Recommendation</div>
                <div class="detail-sidebar-row">
                    <span class="k">From</span>
                    <span class="v"><?= htmlspecialchars($recommendation['recommender_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-sidebar-row">
                    <span class="k">Relationship</span>
                    <span class="v"><?= htmlspecialchars($recommendation['recommender_relationship'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-sidebar-row">
                    <span class="k">Status</span>
                    <span class="detail-status-pill <?= $recPillClass ?>"><?= $recPillText ?></span>
                </div>

                <div class="mt-2">
                    <?php if ($recStatus === 'completed'): ?>
                        <a href="#" class="detail-sidebar-action" style="color: rgb(7,5,55);" data-bs-toggle="modal" data-bs-target="#recModal<?= $recommendation['id'] ?>">
                            <i class="bi bi-eye me-1"></i>View letter
                        </a>
                        <!-- Modal -->
                        <div class="modal fade" id="recModal<?= $recommendation['id'] ?>" tabindex="-1" aria-labelledby="recModalLabel<?= $recommendation['id'] ?>" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
                              <div class="modal-header" style="background: rgb(7,5,55); border: none; padding: 20px 24px;">
                                <div>
                                  <h5 class="modal-title text-white mb-1" id="recModalLabel<?= $recommendation['id'] ?>" style="font-weight: 600;">
                                    Letter of Recommendation
                                  </h5>
                                  <div style="font-size: 13px; color: rgba(255,255,255,0.7);">
                                    For <?= htmlspecialchars($recommendation['applicant_name']) ?>
                                  </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body" style="padding: 28px 30px; background: #fbfbfc;">

                                <!-- Recommender identity strip -->
                                <div class="d-flex align-items-center gap-3 mb-4 pb-3" style="border-bottom: 1px solid #e9e9ee;">
                                  <div style="width: 44px; height: 44px; border-radius: 50%; background: rgb(7,5,55); color: #C5A059; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">
                                    <?= htmlspecialchars(strtoupper(substr($recommendation['recommender_name'] ?? '?', 0, 1))) ?>
                                  </div>
                                  <div>
                                    <div class="fw-semibold" style="font-size: 15px;"><?= htmlspecialchars($recommendation['recommender_name'] ?? 'N/A') ?></div>
                                    <div class="text-muted" style="font-size: 13px;">
                                      <?= htmlspecialchars($recommendation['recommender_relationship'] ?? 'N/A') ?>
                                      <?php if (!empty($recommendation['completed_date'])): ?>
                                        &bull; Submitted <?= date('F j, Y', strtotime($recommendation['completed_date'])) ?>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>

                                <!-- Letter content -->
                                <div class="letter-paper">
                                    <div class="letter-quote-mark">&ldquo;</div>
                                    <div class="letter-body">
                                        <?= $recommendation['recommendation'] ?>
                                    </div>
                                </div>

                              </div>
                              <div class="modal-footer" style="border-top: 1px solid #ececf1; padding: 16px 24px;">
                                <button type="button" class="btn" style="background: rgb(7,5,55); color: #fff;" data-bs-dismiss="modal">Close</button>
                              </div>
                            </div>
                          </div>
                        </div>
                    <?php elseif ($recStatus === 'not_sent'): ?>
                        <a href="send_recommendation.php?id=<?= $recommendation['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="detail-sidebar-action" style="color: rgb(7,5,55);">
                            <i class="bi bi-send me-1"></i>Send request
                        </a>
                    <?php else: ?>
                        <span class="text-muted" style="font-size: 12.5px;"><i class="bi bi-clock me-1"></i>Awaiting response</span>
                    <?php endif; ?>
                </div>
            </div>

    </div>

    <!-- Document: application content -->
    <div class="col-lg-9 ps-lg-4">

            <div class="detail-doc-section">
                <div class="detail-doc-head"><i class="bi bi-bank2"></i><div class="detail-doc-title">Post-Secondary Plans</div></div>
                <div class="detail-doc-grid">
                    <div>
                        <div class="detail-doc-label">Intended School</div>
                        <div class="detail-doc-value"><?= htmlspecialchars($application['intended_school'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div class="detail-doc-label">Intended Major</div>
                        <div class="detail-doc-value"><?= htmlspecialchars($application['intended_major'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <div class="detail-doc-section">
                <div class="detail-doc-head"><i class="bi bi-people-fill"></i><div class="detail-doc-title">Activities & Leadership</div></div>
                <div class="detail-doc-label">Extracurricular Activities</div>
                <div class="detail-doc-value mb-3"><?= nl2br(htmlspecialchars($application['extracurricular'] ?? 'N/A')) ?></div>
                <div class="detail-doc-label">Leadership Roles</div>
                <div class="detail-doc-value mb-3"><?= nl2br(htmlspecialchars($application['leadership'] ?? 'N/A')) ?></div>
                <div class="detail-doc-label">Community Service</div>
                <div class="detail-doc-value"><?= nl2br(htmlspecialchars($application['community_service'] ?? 'N/A')) ?></div>
            </div>

            <?php
                $essayText = $application['essay'] ?? '';
                $wordCount = str_word_count($essayText);
            ?>
            <div class="detail-doc-section">
                <div class="detail-doc-head">
                    <i class="bi bi-file-text-fill"></i>
                    <div class="detail-doc-title">Essay</div>
                    <div class="detail-word-count"><?= $wordCount ?> words</div>
                </div>
                <div style="font-size: 13.5px; color: #6c757d; font-style: italic; margin-bottom: 10px;">
                    In 500-750 words, please tell us about yourself...
                </div>
                <div class="detail-essay-box">
                    <?= nl2br(htmlspecialchars($essayText)) ?>
                </div>
            </div>

            <div class="detail-doc-section">
                <div class="detail-doc-head"><i class="bi bi-info-circle-fill"></i><div class="detail-doc-title">Additional Details</div></div>
                <div class="detail-doc-grid">
                    <div>
                        <div class="detail-doc-label">Financial Need</div>
                        <div class="detail-doc-value"><?= nl2br(htmlspecialchars($application['financial_need'] ?? 'N/A')) ?></div>
                    </div>
                    <div>
                        <div class="detail-doc-label">Additional Notes</div>
                        <div class="detail-doc-value"><?= nl2br(htmlspecialchars($application['additional_information'] ?? 'N/A')) ?></div>
                    </div>
                </div>
            </div>

    </div>

</div>

  </div>

<?php else: ?>
<div class="alert alert-warning">
    Application not found.
</div>
<?php endif; ?>

</div>

</div>
</main>


<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>





<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Styled confirmation for designating the final recipient, replacing the
// old native confirm() popup.
(function() {
    const form = document.getElementById('designateForm');
    if (!form) return;

    const recommendationReceived = <?= $recommendationReceived ? 'true' : 'false' ?>;
    const applicantName = <?= json_encode($application['first_name'] . ' ' . $application['last_name'], JSON_HEX_TAG | JSON_HEX_APOS) ?>;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        Swal.fire({
            title: recommendationReceived ? 'Designate as Final Recipient?' : 'Recommendation not yet received',
            html: recommendationReceived
                ? `This will mark <strong>${applicantName}</strong> as this cycle's final recipient.`
                : `${applicantName}'s recommendation hasn't been received yet. Designate them as the final recipient anyway?`,
            icon: recommendationReceived ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: recommendationReceived ? 'Yes, designate' : 'Designate Anyway',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
})();
</script>






</body>
</html>