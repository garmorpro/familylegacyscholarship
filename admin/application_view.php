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

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.0.0">
    <title>Application Portal - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; border-color: rgb(241,242,243) !important; padding: 0 !important;">
  
  <!-- Top header remains white -->
  <div class="card-header bg-white shadow-sm" style="padding: 1.5rem !important; padding-bottom: 0 !important;">
    <div class="mb-3">
        <!-- Back link -->
        <a href="<?= BASE_URL ?>/admin/" class="text-decoration-none text-muted d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Back to applications
        </a>
    </div>
  

  <!-- Card body with pink background -->
  

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

<div class="row align-items-center py-3">
    <!-- Left: Name + Major/School -->
    <div class="col-md-6">
        <h2 class="fw-semibold mb-1">
            <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
            <?php if ($isArchived): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-2" style="font-size: 12px; vertical-align: middle;">
                    <i class="bi bi-archive me-1"></i>Archived
                </span>
            <?php endif; ?>
        </h2>
        <div class="text-muted">
            <?= htmlspecialchars($application['intended_major']) ?> &bull; <?= htmlspecialchars($application['intended_school']) ?>
        </div>
        <?php if ($isArchived): ?>
            <div class="text-muted mt-1" style="font-size: 13px;">
                Archived <?= date('M j, Y', strtotime($application['archived_at'])) ?> — this record is read-only.
            </div>
        <?php endif; ?>
    </div>

    <!-- Right: Reviewed button + Submission Date & Status -->
<div class="col-md-6 text-md-end mt-3 mt-md-0">

    <?php if (!$isArchived && $application['application_status'] === 'submitted'): ?>
        <div class="mb-2" style="margin-top: -15px !important; margin-bottom: 15px !important;">
            <form method="POST" action="mark_reviewed.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $application['id'] ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark Reviewed</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (!$isArchived && $application['application_status'] === 'reviewed'): ?>
        <div class="mb-2" style="margin-top: -15px !important; margin-bottom: 15px !important;">
            <form method="POST" action="mark_final_review.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $application['id'] ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm">Advance to Final Review</button>
            </form>
        </div>
    <?php endif; ?>

    <?php
    // Check if any *active* (non-archived) application has already been
    // selected as final recipient — archived recipients from past cycles
    // don't count, or a new cycle could never designate a recipient again.
    $stmt = $pdo->query("SELECT COUNT(*) FROM scholarship_applications WHERE application_status = 'final_recipient' AND archived_at IS NULL");
    $finalCount = (int) $stmt->fetchColumn();
    ?>

    <?php if (!$isArchived && $application['application_status'] === 'final_review' && $finalCount === 0): ?>
        <div class="mb-2" style="margin-top: -15px !important; margin-bottom: 15px !important;">
            <form method="POST" action="mark_final_selected.php" class="d-inline"
                <?php if (!$recommendationReceived): ?>
                onsubmit="return confirm('This applicant\'s recommendation hasn\'t been received yet. Designate them as the final recipient anyway?');"
                <?php endif; ?>
            >
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $application['id'] ?>">
                <button type="submit" class="btn btn-outline-success btn-sm">
                    Designate Final Recipient
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (in_array($application['application_status'], ['submitted', 'reviewed', 'final_review'], true)): ?>
        <div class="mb-2" style="font-size: 13px;">
            <span class="text-muted">Recommendation:</span>
            <?php if ($recommendationStatus === 'completed'): ?>
                <span class="text-success fw-semibold">Received</span>
            <?php elseif ($recommendationStatus === 'sent'): ?>
                <span class="text-secondary fw-semibold">Sent, awaiting response</span>
            <?php else: ?>
                <span class="text-danger fw-semibold">Not sent yet</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div>
        <span class="fw-semibold me-2">Submission Date:</span>
        <?= date('M j, Y', strtotime($application['submitted_at'])) ?>
    </div>
    <div class="mt-1">
        <span class="fw-semibold me-2">Status:</span>
        <span class="badge
            <?php
                echo match ($application['application_status']) {
                    'submitted' => 'bg-primary-subtle text-primary',
                    'reviewed'  => 'bg-secondary-subtle text-secondary',
                    'final_review'  => 'bg-success-subtle text-success',
                    'final_recipient'  => 'bg-info-subtle text-info',
                    default     => 'bg-light text-dark'
                };
            ?>">
            <?= ucwords(str_replace('_', ' ', $application['application_status'])) ?>
        </span>
    </div>
</div>

</div>

</div>

<div class="card-body" style="background-color: #eaefff; padding: 1.5rem !important; border: none !important;">

<div class="row mt-4">

    <!-- LEFT COLUMN: 25% -->
    <div class="col-lg-4">

        <!-- Contact Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-body">
                <h5 class="card-title fw-semibold mb-3">Contact Information</h5>
                <div class="mb-2">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-envelope me-2 text-primary"></i> <span class="text-muted">Email</span>
                    </div>
                    <a href="mailto:<?= htmlspecialchars($application['email'] ?? 'N/A') ?>"><span class="fw-semibold"><?= htmlspecialchars($application['email'] ?? 'N/A') ?></span></a>
                </div>
                <hr>
                <div class="mb-0">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-telephone me-2 text-primary"></i> <span class="text-muted">Phone</span>
                    </div>
                    <span class="fw-semibold"><?= htmlspecialchars($application['phone'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- Academic Profile Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-body">
                <h5 class="card-title fw-semibold mb-3">Academic Profile</h5>
                <div class="mb-2">
                    <span class="text-muted">GPA</span> <br> <span class="fw-semibold"><?= htmlspecialchars($application['gpa'] ?? 'N/A') ?></span>
                </div>
                <hr>
                <div class="mb-2">
                    <span class="text-muted">Expected Graduation Year</span><br> <span class="fw-semibold"><?= htmlspecialchars($application['expected_graduation_year'] ?? 'N/A') ?></span>
                </div>
                <hr>
                <div class="mb-0">
                    <span class="text-muted">Institution Type</span> <br> <span class="fw-semibold"><?= htmlspecialchars($application['institution_type'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- Recommendation Card -->
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

?>


         
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title fw-semibold mb-0">Recommendation</h5>
                    <?php
$status = strtolower($recommendation['recommender_status'] ?? 'not sent');

switch ($status) {
    case 'completed':
        $iconClass = 'bi-eye-fill text-success';
        $iconTitle = 'Completed';
        break;
    case 'sent':
        $iconClass = 'bi-clock-fill text-secondary';
        $iconTitle = 'Send';
        break;
    case 'sent':
    case 'not_sent':
    default:
        $iconClass = 'bi-send-fill text-primary';
        $iconTitle = 'Not Sent';
        break;
}
?>
<div class="d-flex gap-1">
    <?php if ($status === 'not_sent'): ?>
        <!-- Not sent: show clickable icon that triggers alert -->
        <i class="bi <?= $iconClass ?>" title="<?= $iconTitle ?>"
       style="cursor:pointer;"
       onclick="window.location.href='send_recommendation.php?id=<?= $recommendation['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>'">
    </i>
    <?php elseif ($status === 'completed'): ?>
        <!-- Completed: open modal -->
        <i class="bi <?= $iconClass ?>" title="<?= $iconTitle ?>" 
           style="cursor:pointer;" 
           data-bs-toggle="modal" 
           data-bs-target="#recModal<?= $recommendation['id'] ?>">
        </i>
        <!-- Modal -->
    <div class="modal fade" id="recModal<?= $recommendation['id'] ?>" tabindex="-1" aria-labelledby="recModalLabel<?= $recommendation['id'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="recModalLabel<?= $recommendation['id'] ?>">Recommendation for <?= htmlspecialchars($recommendation['applicant_name']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
    <!-- Recommendation content rendered as HTML -->
    <?= $recommendation['recommendation'] ?>
</div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <?php else: ?>
        <!-- Pending or Sent: static icon -->
        <i class="bi <?= $iconClass ?>" title="<?= $iconTitle ?>"></i>
    <?php endif; ?>
</div>


                </div>

                <div class="mb-2">
    <span class="text-muted">Recommender</span> <br>
    <span class="fw-semibold"><?= htmlspecialchars($recommendation['recommender_name'] ?? 'N/A') ?></span>
</div>
<div class="mb-2">
    <span class="text-muted">Relationship</span> <br>
    <span class="fw-semibold"><?= htmlspecialchars($recommendation['recommender_relationship'] ?? 'N/A') ?></span>
</div>
<div class="mb-2">
    <span class="text-muted">Email</span> <br> 
    <span class="fw-semibold">
        <a href="mailto:<?= htmlspecialchars($recommendation['recommender_email'] ?? '') ?>">
            <?= htmlspecialchars($recommendation['recommender_email'] ?? 'N/A') ?>
        </a>
    </span>
</div>

<?php
    $status = strtolower($recommendation['recommender_status'] ?? '');
    switch ($status) {
        case 'completed': $badgeClass='bg-success'; $badgeText='Completed'; break;
        case 'sent': $badgeClass='bg-primary'; $badgeText='Sent'; break;
        case 'not_sent': $badgeClass='bg-secondary'; $badgeText='Not Sent'; break;
        default: $badgeClass='bg-secondary'; $badgeText=htmlspecialchars($recommendation['recommender_status']);
    }
?>
<div class="mb-0">
    <span class="text-muted">Status</span> <br>
    <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2"><?= $badgeText ?></span>
</div>


            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: 75% -->
    <div class="col-lg-8">

        <!-- Post-Secondary Plans Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-header bg-white d-flex align-items-center gap-2" style="border: none !important; padding-top: 30px; padding-left: 25px; margin-bottom: -20px !important;">
                <div class="d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: #6c757d; border-radius: 4px;">
                    <i class="bi bi-mortarboard text-white"></i>
                </div>
                <h5 class="mb-0 fw-semibold">Post-Secondary Plans</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-muted">Intended School</span> <br>
                    <span class="fw-semibold"><?= htmlspecialchars($application['intended_school'] ?? 'N/A') ?></span>
                </div>
                <div class="mb-0">
                    <span class="text-muted">Intended Major</span> <br>
                    <span class="fw-semibold"><?= htmlspecialchars($application['intended_major'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- Activities & Leadership Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-header bg-white d-flex align-items-center gap-2" style="border: none !important; padding-top: 30px; padding-left: 25px; margin-bottom: -20px !important;">
                <div class="d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: #198754; border-radius: 4px;">
                    <i class="bi bi-award text-white"></i>
                </div>
                <h5 class="mb-0 fw-semibold">Activities & Leadership</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-semibold mb-1">Extracurricular Activities:</div>
                    <div><?= nl2br(htmlspecialchars($application['extracurricular'] ?? 'N/A')) ?></div>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="fw-semibold mb-1">Leadership Roles:</div>
                    <div><?= nl2br(htmlspecialchars($application['leadership'] ?? 'N/A')) ?></div>
                </div>
                <hr>
                <div>
                    <div class="fw-semibold mb-1">Community Service:</div>
                    <div><?= nl2br(htmlspecialchars($application['community_service'] ?? 'N/A')) ?></div>
                </div>
            </div>
        </div>

        <!-- Essay Card -->
<div class="card mb-3 shadow-sm" style="border-radius: 12px; border: none; overflow: hidden;">
    <!-- Card Header -->
    <div class="card-header bg-white d-flex align-items-center gap-2" style="padding: 20px 10px; border-bottom: none !important;">
        <div class="d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: #0d6efd; border-radius: 6px;">
            <i class="bi bi-file-earmark-text text-white"></i>
        </div>
        <h5 class="mb-0 fw-semibold">Essay</h5>
        <!-- Word Count small badge -->
        <?php
            $essayText = $application['essay'] ?? '';
            $wordCount = str_word_count($essayText);
        ?>
        <span style="
            font-size: 12px;
            color: #6c757d;
            margin-left: auto;
            font-weight: 500;
        ">
            <?= $wordCount ?> words
        </span>
    </div>
    <p style="font-size: 14px;">
        In 500-750 words, please tell us about yourself...
    </p>

    <!-- Card Body -->
    <div class="card-body" style="padding: 20px 25px; background-color: #f8f9fa; border-radius: 0 0 12px 12px; border-top: 1px solid #e9ecef;">
        <div style="line-height: 1.6; color: #343a40; font-size: 14px;">
            <?= nl2br(htmlspecialchars($essayText)) ?>
        </div>
    </div>
</div>


        <!-- Additional Details Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-header bg-white d-flex align-items-center gap-2" style="border: none !important; padding-top: 30px; padding-left: 25px; margin-bottom: -20px !important;">
                <div class="d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: #6c757d; border-radius: 4px;">
                    <i class="bi bi-info-circle text-white"></i>
                </div>
                <h5 class="mb-0 fw-semibold">Additional Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-semibold mb-1">Financial Need:</div>
                    <div><?= nl2br(htmlspecialchars($application['financial_need'] ?? 'N/A')) ?></div>
                </div>
                <hr>
                <div>
                    <div class="fw-semibold mb-1">Additional Notes:</div>
                    <div><?= nl2br(htmlspecialchars($application['additional_information'] ?? 'N/A')) ?></div>
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

</div>
</main>


<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>





<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>






</body>
</html>