<?php
require_once '../app/db.php';
require_once '../path.php';
require_once '../app/functions.php';

$token = $_GET['token'] ?? '';
require_once '../app/committee_access.php';
// Falls through only once the token and code are both verified.

// Pull the essay prompt from Settings, same as the admin detail page.
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
}

function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? htmlspecialchars($settings[$key], ENT_QUOTES, 'UTF-8') : $default;
}

const DEFAULT_ESSAY_PROMPT = 'In 500–750 words, please tell us about yourself, your goals, and what makes you a strong candidate for this scholarship.';

$appId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Restricted to Final Review, active applications only -- a committee
// member can't view any other application even if they guess/change the id
// in the URL, regardless of what the token/code already gate.
$stmt = $pdo->prepare("
    SELECT * FROM scholarship_applications
    WHERE id = :id AND application_status = 'final_review' AND archived_at IS NULL
");
$stmt->execute([':id' => $appId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if ($application) {
    $recommendationStmt = $pdo->prepare("
        SELECT
            r.id,
            r.recommender_name,
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
    $recommendation['applicant_name'] = ($recommendation['first_name'] ?? '') . ' ' . ($recommendation['last_name'] ?? '');

    $recStatus = strtolower($recommendation['recommender_status'] ?? 'not_sent');
    switch ($recStatus) {
        case 'completed': $recPillClass = 'completed'; $recPillText = 'Completed'; break;
        case 'sent':      $recPillClass = 'sent';      $recPillText = 'Sent';      break;
        default:          $recPillClass = 'not-sent';  $recPillText = 'Not Sent';
    }
}

// This member's current pick, if any -- shown to them only.
$voteStmt = $pdo->prepare("SELECT application_id FROM committee_votes WHERE committee_member_id = :member_id");
$voteStmt->execute([':member_id' => $committeeMemberId]);
$myPickId = (int) $voteStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $application ? htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) : 'Application' ?> - Committee Review</title>
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
        .back-link { font-size: 13.5px; color: #9a9aa5; font-weight: 600; }
        .back-link:hover { color: rgb(7,5,55); }
        .pick-btn { border: none; border-radius: 8px; padding: 11px 22px; font-size: 14.5px; font-weight: 600; }
        .pick-btn.unpicked { background: rgb(233,236,255); color: rgb(7,5,55); }
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
            &bull; <a href="review.php?token=<?= urlencode($token) ?>&switch_identity=1">Not you?</a>
        </div>
    </div>
</div>

<div class="container pb-5" style="max-width: 1160px;">

<?php if ($application): ?>

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; border-color: rgb(241,242,243) !important; padding: 0 !important;">
  <div class="case-accent"></div>

  <div style="padding: 28px 32px 24px;">
    <a href="review.php?token=<?= urlencode($token) ?>" class="text-decoration-none back-link d-inline-flex align-items-center mb-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Final Review list
    </a>

    <div class="row align-items-start">
        <div class="col-md-6">
            <div style="font-size: 26px; font-weight: 800; color: #16151f; letter-spacing: -0.01em; margin-bottom: 2px;">
                <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
            </div>
            <div style="font-size: 14.5px; color: #6c757d; font-weight: 500;">
                <?= htmlspecialchars($application['intended_major']) ?> &bull; <?= htmlspecialchars($application['intended_school']) ?>
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div class="mb-2">
                <span class="meta-label">Submitted&nbsp; </span>
                <span class="meta-value"><?= date('M j, Y', strtotime($application['submitted_at'])) ?></span>
            </div>
            <?php $isMyPick = ((int) $application['id'] === $myPickId); ?>
            <button type="button" class="pick-btn <?= $isMyPick ? 'picked' : 'unpicked' ?>" id="pickBtn" data-app-id="<?= (int) $application['id'] ?>">
                <i class="bi <?= $isMyPick ? 'bi-star-fill' : 'bi-star' ?> me-1"></i><?= $isMyPick ? 'This Is My Pick' : 'Pick as My Candidate' ?>
            </button>
        </div>
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

                <?php if ($recStatus === 'completed'): ?>
                    <div class="mt-2">
                        <a href="#" class="detail-sidebar-action" style="color: rgb(7,5,55);" data-bs-toggle="modal" data-bs-target="#recModal<?= $recommendation['id'] ?>">
                            <i class="bi bi-eye me-1"></i>View letter
                        </a>
                    </div>
                <?php endif; ?>
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
                    <?= getSetting('essay_prompt', DEFAULT_ESSAY_PROMPT) ?>
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

</div>

<?php if ($recStatus === 'completed'): ?>
    <div class="modal fade" id="recModal<?= $recommendation['id'] ?>" tabindex="-1" aria-labelledby="recModalLabel<?= $recommendation['id'] ?>" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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

            <div class="letter-paper">
                <div class="letter-quote-mark">&ldquo;</div>
                <div class="letter-body">
                    <?= sanitize_recommendation_html($recommendation['recommendation'] ?? '') ?>
                </div>
            </div>

          </div>
          <div class="modal-footer" style="border-top: 1px solid #ececf1; padding: 16px 24px;">
            <button type="button" class="btn" style="background: rgb(7,5,55); color: #fff;" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
<?php endif; ?>

<?php else: ?>
    <div class="alert alert-warning">
        This application isn't available for review right now &mdash; it may have moved out of Final Review.
    </div>
<?php endif; ?>

</div>

<?php require_once ROOT_PATH . '/assets/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.modal[id^="recModal"]').forEach(function(modalEl) {
    modalEl.addEventListener('mousedown', function(e) {
        if (e.target === modalEl) {
            const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
    });
});
</script>

<?php if ($application): ?>
<script>
document.getElementById('pickBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;

    fetch('vote.php?token=<?= urlencode($token) ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ application_id: btn.dataset.appId })
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
});
</script>
<?php endif; ?>

</body>
</html>
