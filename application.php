<?php
require_once 'app/db.php';
require_once 'path.php';

// Pull the actual dates set in the admin Settings page, rather than
// hardcoding them here where they'd inevitably go stale.
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
}

function formatTimelineDate(?string $value): string {
    if (empty($value)) {
        return 'To be announced';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('F j, Y', $timestamp) : 'To be announced';
}

function formatTimelineRange(?string $start, ?string $end): string {
    $hasStart = !empty($start);
    $hasEnd = !empty($end);
    if (!$hasStart && !$hasEnd) {
        return 'To be announced';
    }
    if ($hasStart && !$hasEnd) {
        return formatTimelineDate($start) . ' onward';
    }
    if (!$hasStart && $hasEnd) {
        return 'Through ' . formatTimelineDate($end);
    }
    return formatTimelineDate($start) . ' &ndash; ' . formatTimelineDate($end);
}

$applicationOpenDate  = formatTimelineDate($settings['application_open'] ?? null);
$applicationCloseDate = formatTimelineDate($settings['application_closed'] ?? null);
$reviewPeriodRange    = formatTimelineRange($settings['review_start'] ?? null, $settings['review_end'] ?? null);
$announcementDate     = formatTimelineDate($settings['announcement_date'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/styles.css?v=<?= time() ?>">
    <title>Application - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">
    <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">

        <div class="card-body">

            <h2 class="text-center">
                Application, Selection & Timeline
            </h2>
            <p class="text-center">Please review these requirements carefully before applying.</p>

            <h4>
                Application Process
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            
            <div class="row align-items-start g-3">
  <!-- Step / Feature 1 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <!-- Number Circle -->
    <div class="number-circle me-3">1</div>

    <!-- Text Content -->
    <div>
      <h6 class="mb-1">Review Requirements</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        Read through the eligibility requirements and selection criteria carefully to ensure you qualify.
      </p>
    </div>
  </div>

  <!-- Step / Feature 2 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="number-circle me-3">2</div>
    <div>
      <h6 class="mb-1">Complete the Online Application</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        Fill out the application form with your personal information, educational plans, and activities.
      </p>
    </div>
  </div>

  <!-- Step / Feature 3 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="number-circle me-3">3</div>
    <div>
      <h6 class="mb-1">Write Your Essay(s)</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        Respond thoughtfully to the essay prompt(s). This is your opportunity to share your story, goals, and what makes you a strong candidate.
      </p>
    </div>
  </div>

  <!-- Step / Feature 4 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="number-circle me-3">4</div>
    <div>
      <h6 class="mb-1">Submit a Recommendation</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        Provide contact information for one person who can speak to your character and potential (teacher, counselor, coach, employer, or community leader).
      </p>
    </div>
  </div>

  <!-- Step / Feature 5 -->
  <div class="col-12 d-flex align-items-start">
    <div class="number-circle me-3">5</div>
    <div>
      <h6 class="mb-1">Submit Before the Deadline</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        Ensure all materials are submitted by <strong><?= $applicationCloseDate ?></strong>. Late applications will not be accepted.
      </p>
    </div>
  </div>

</div>

<!-- <a href="/application-form.php" class="btn btn-primary mt-4"><i class="bi bi-file-earmark-text me-2"></i>Start Your Application</a> -->

<h4 class="mt-4">
                Selection Process
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            
            <div class="row align-items-start g-3">
  <!-- Step / Feature 1 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <!-- Number Circle -->
    <div class=" me-3"><i class="bi bi-person text-primary" style="font-size: 22px !important;"></i></div>

    <!-- Text Content -->
    <div>
      <h6 class="mb-1">Reviewed by the Morgan Family Selection Committee</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        All applications are reviewed by a committee of Morgan family members who are committed to selecting a deserving recipient.
      </p>
    </div>
  </div>

  <!-- Step / Feature 2 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="me-3"><i class="bi bi-check2-circle text-primary" style="font-size: 22px !important;"></i></div>
    <div>
      <h6 class="mb-1">Holistic, Criteria-Based Evaluation</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        The committee evaluates each application based on character, leadership, commitment to growth, and financial need using a fair and transparent process.
      </p>
    </div>
  </div>

  <!-- Step / Feature 3 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="me-3"><i class="bi bi-file-earmark-text text-primary" style="font-size: 22px !important;"></i></div>
    <div>
      <h6 class="mb-1">Recipient Notification</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        The selected recipient will be notified directly via email and/or phone. The announcement date is listed in the timeline below.
      </p>
    </div>
  </div>

  </div>


<h4 class="mt-4">
                Annual Timeline
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            
            <div class="row align-items-start g-3">
  <!-- Step / Feature 1 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <!-- Number Circle -->
    <div class="me-3"><i class="bi bi-calendar2 text-primary" style="font-size: 22px !important;"></i></div>

    <!-- Text Content -->
    <div>
      <h6 class="mb-1">Applications Open</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        <?= $applicationOpenDate ?>
      </p>
    </div>
  </div>

  <!-- Step / Feature 2 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="me-3"><i class="bi bi-calendar2 text-primary" style="font-size: 22px !important;"></i></div>
    <div>
      <h6 class="mb-1">Application Deadline</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        <?= $applicationCloseDate ?>
      </p>
    </div>
  </div>

  <!-- Step / Feature 3 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="me-3"><i class="bi bi-calendar2 text-primary" style="font-size: 22px !important;"></i></div>
    <div>
      <h6 class="mb-1">Estimated Review Period</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        <?= $reviewPeriodRange ?>
      </p>
    </div>
  </div>

  <!-- Step / Feature 4 -->
  <div class="col-12 d-flex align-items-start mb-3">
    <div class="me-3"><i class="bi bi-calendar2 text-primary" style="font-size: 22px !important;"></i></div>
    <div>
      <h6 class="mb-1">Recipient Announcement</h6>
      <p class="mb-0" style="font-size: 14px; color: #555;">
        <?= $announcementDate ?>
      </p>
    </div>
  </div>

</div>

<hr>

<div class="d-flex justify-content-center">
<a href="<?= BASE_URL ?>/application-form.php" class="btn mt-4" style="background-color: rgb(7,5,55); color:white; font-size: 18px !important;"><i class="bi bi-file-earmark-text me-2"></i>Start Your Application</a>
</div>







            

            
        </div>
    </div>
</div>

</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Get current page path
  const currentPath = window.location.pathname;

  // Select all navbar links
  const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

  navLinks.forEach(link => {
    // Remove any existing active class
    link.classList.remove('active');

    // If the link href matches the current path, add active
    if (link.getAttribute('href') === currentPath) {
      link.classList.add('active');
    }
  });
</script>


</body>
</html>