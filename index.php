<?php
require_once 'app/db.php';
require_once 'path.php';

try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
}

// Helper
function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? htmlspecialchars($settings[$key], ENT_QUOTES, 'UTF-8') : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/styles.css?v=<?= time() ?>">
    <title>Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  <!-- Image fills top corners, edge-to-edge -->
  <img src="assets/images/final_beach_stairs.jpg"
     class="card-img-top about-main-img"
     alt="..."
     style="
        display:block;
        width:100%;
        height:200px;
        object-fit: cover;
        object-position: 20% 33% !important;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        margin:0;
        padding:0;
     ">

     <?php
$applicationOpen = getSetting('application_open');   // e.g., 2026-02-15
$applicationClose = getSetting('application_closed'); // e.g., 2026-04-15
$applicationCloseYear = $applicationClose ? date("Y", strtotime($applicationClose)) : date("Y");

$today = date('Y-m-d');

if (empty($applicationOpen) || empty($applicationClose)) {
    $cycleState = 'unset';      // dates not configured yet
} elseif ($today < $applicationOpen) {
    $cycleState = 'not_open';   // hasn't opened yet — distinct from "closed"
} elseif ($today > $applicationClose) {
    $cycleState = 'closed';
} else {
    $cycleState = 'open';
}
?>


  <!-- Text with padding preserved -->
  <div class="card-body text-center">
    <h2 class="card-title">Supporting Excellence at Battery Creek High School</h2>
    <p class="card-text">Honoring character, leadership, and a commitment to growth for graduating seniors pursuing higher education.</p>
    <div class="mb-2">
<?php if ($cycleState === 'open'): ?>
  <span class="badge rounded-pill mb-1"
        style="background-color: rgb(226,251,232); color: rgb(43,101,54); font-weight: 400 !important;">
    <i class="bi bi-check2-circle me-1"></i>
    Applications Now Open
  </span>
<?php elseif ($cycleState === 'not_open'): ?>
  <span class="badge rounded-pill mb-1"
        style="background-color: rgb(255,247,224); color: rgb(146,108,17); font-weight: 400 !important;">
    <i class="bi bi-hourglass-split me-1"></i>
    Opens <?= date("M j", strtotime($applicationOpen)) ?>
  </span>
<?php elseif ($cycleState === 'closed'): ?>
  <span class="badge rounded-pill mb-1"
        style="background-color: rgb(253,235,235); color: rgb(153,27,27); font-weight: 400 !important;">
    <i class="bi bi-x-circle me-1"></i>
    Application Closed
  </span>
<?php else: ?>
  <span class="badge rounded-pill mb-1"
        style="background-color: rgb(241,242,246); color: rgb(90,97,110); font-weight: 400 !important;">
    <i class="bi bi-calendar2 me-1"></i>
    Dates Coming Soon
  </span>
<?php endif; ?>

      <br>
      <p class="text-muted" style="font-size: 12px;">
        Class of <?= $applicationCloseYear ?>
      </p>
    </div>

    <div class="card countdown-card mx-auto" style="background-color: rgb(7,5,55); color: white; border: none; padding: .5rem !important;">
  <div class="card-body text-center">

    <?php if ($cycleState === 'open' || $cycleState === 'not_open'): ?>
      <?php
        $countdownTarget = $cycleState === 'open'
            ? $applicationClose . 'T23:59:59'
            : $applicationOpen . 'T00:00:00';
        $countdownLabel = $cycleState === 'open'
            ? 'Time remaining to submit your application:'
            : 'Applications open in:';
      ?>
      <p style="font-size: 14px;">
        <i class="bi bi-clock"></i> <?= $countdownLabel ?>
      </p>

      <div id="countdown-boxes" class="d-flex gap-2 flex-wrap justify-content-center" data-target="<?= htmlspecialchars($countdownTarget) ?>">
        <div class="text-center">
          <div class="count-box" id="days">0</div>
          <div style="font-size: 12px;">Days</div>
        </div>
        <div class="text-center">
          <div class="count-box" id="hours">0</div>
          <div style="font-size: 12px;">Hours</div>
        </div>
        <div class="text-center">
          <div class="count-box" id="minutes">0</div>
          <div style="font-size: 12px;">Minutes</div>
        </div>
        <div class="text-center">
          <div class="count-box" id="seconds">0</div>
          <div style="font-size: 12px;">Seconds</div>
        </div>
      </div>

      <p class="mt-2 mb-0" style="font-size: 12px; opacity: .75;">
        <?= $cycleState === 'open'
            ? 'Deadline: ' . date('F j, Y', strtotime($applicationClose))
            : 'Opens ' . date('F j, Y', strtotime($applicationOpen)) ?>
      </p>

    <?php elseif ($cycleState === 'closed'): ?>
      <i class="bi bi-check2-circle" style="font-size: 28px; opacity: .85;"></i>
      <p class="mt-2 mb-1" style="font-size: 16px; font-weight: 600;">
        This year's application window has closed.
      </p>
      <p class="mb-0" style="font-size: 13px; opacity: .75;">
        Thank you to everyone who applied &mdash; stay tuned for next year's dates.
      </p>

    <?php else: ?>
      <i class="bi bi-calendar2" style="font-size: 28px; opacity: .85;"></i>
      <p class="mt-2 mb-0" style="font-size: 14px;">
        Application dates haven't been announced yet. Check back soon!
      </p>
    <?php endif; ?>

  </div>
</div>

<div class="row g-3 mb-3">

  <!-- Card 1: Award Amount -->
  <div class="col-12 col-md-6">
    <div class="card modern-vertical-card text-center p-4 border-0 shadow-sm" style="border-radius: 16px;">
      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; border-radius: 50%; background: rgb(7,5,55);">
        <i class="bi bi-currency-dollar" style="font-size: 1.4rem; color: rgb(197,160,89);"></i>
      </div>
      <div class="text-uppercase text-muted mb-1" style="letter-spacing: .06em; font-size: 11px;">Award Amount</div>
      <div class="fw-bold" style="font-size: 1.6rem; color: rgb(7,5,55);">$<?= number_format(getSetting('award_amount', 0)) ?></div>
      <div style="width: 28px; height: 3px; background: rgb(197,160,89); margin: 12px auto 0; border-radius: 2px;"></div>
    </div>
  </div>

  <!-- Card 2: Application Deadline -->
  <?php
    if ($cycleState === 'not_open') {
        $label = "Application Opens";
        $dateToShow = $applicationOpen;
    } elseif ($cycleState === 'closed') {
        $label = "Applications Closed";
        $dateToShow = $applicationClose;
    } elseif ($cycleState === 'open') {
        $label = "Application Deadline";
        $dateToShow = $applicationClose;
    } else {
        $label = "Dates Coming Soon";
        $dateToShow = null;
    }
  ?>

  <div class="col-12 col-md-6">
    <div class="card modern-vertical-card text-center p-4 border-0 shadow-sm" style="border-radius: 16px;">
      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; border-radius: 50%; background: rgb(7,5,55);">
        <i class="bi bi-calendar2" style="font-size: 1.4rem; color: rgb(197,160,89);"></i>
      </div>
      <div class="text-uppercase text-muted mb-1" style="letter-spacing: .06em; font-size: 11px;"><?= $label ?></div>
      <div class="fw-bold" style="font-size: 1.6rem; color: rgb(7,5,55);"><?= $dateToShow ? date("F j, Y", strtotime($dateToShow)) : 'TBD' ?></div>
      <div style="width: 28px; height: 3px; background: rgb(197,160,89); margin: 12px auto 0; border-radius: 2px;"></div>
    </div>
  </div>

</div>

<style>
/* Card hover effect */
.modern-vertical-card {
    transition: transform 0.2s, box-shadow 0.2s;
    background-color: #fff;
}
.modern-vertical-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px rgba(0,0,0,0.1);
}
</style>



<?php if ($cycleState === 'open'): ?>
    <a href="<?= BASE_URL ?>/application-form.php"
       class="btn mt-4"
       style="background-color: rgb(7, 5, 55); color:white; font-size: 18px !important;">
        <i class="bi bi-file-earmark-text me-2"></i>
        Start Your Application
    </a>
<?php endif;
?>
  </div>
</div>



<!-- new card Eligibility -->
 <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  


  <!-- Text with padding preserved -->
  <div class="card-body">
    <h5 class="card-title">Who Can Apply?</h5>
    <ul class="mt-2">
      <li class="pb-3 pt-3">
        Graduating seniors of Battery Creek High School
      </li>
      <li class="pb-3">
        Planning to attend college, university, or trade school
      </li>
      <li class="pb-3">
        Demonstrates character, leadership, and commitment to growth
      </li>
    </ul>

  <a href="<?= BASE_URL ?>/eligibility.php" class="card-link text-decoration-none">View Full Eligibility Requirements <i class="bi bi-arrow-right-short"></i></a>
  </div>
</div>



<!-- next section -->

<div class="row g-3">
  <!-- Card 1 -->
  <div class="col-12 col-md-4">
    <a href="<?= BASE_URL ?>/about.php" style="text-decoration: none; color: inherit;">
      <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
        <div class="card-body">
          <h6 class="card-title" style="font-size: 18px;">About the Scholarship</h6>
          <p class="card-text" style="font-size: 14px;">Learn about the Morgan family's commitment to Battery Creek students</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Card 2 -->
  <div class="col-12 col-md-4">
    <a href="<?= BASE_URL ?>/eligibility.php" style="text-decoration: none; color: inherit;">
      <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
        <div class="card-body">
          <h6 class="card-title" style="font-size: 18px;">Eligibility & Criteria</h6>
          <p class="card-text" style="font-size: 14px;">Review requirements and selection criteria before applying</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Card 3 -->
  <div class="col-12 col-md-4">
    <a href="<?= BASE_URL ?>/application.php" style="text-decoration: none; color: inherit;">
      <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
        <div class="card-body">
          <h6 class="card-title" style="font-size: 18px;">Application Process</h6>
          <p class="card-text" style="font-size: 14px;">Step-by-step guide and timeline for applicants</p>
        </div>
      </div>
    </a>
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

<script>
  // The countdown-boxes element only exists when the server has decided
  // there's an active countdown to show (either "opens in" or "deadline in").
  // In every other state, PHP renders a static message instead — no more
  // fighting Bootstrap's !important display utilities to hide/show things.
  const countdownBoxes = document.getElementById("countdown-boxes");

  if (countdownBoxes) {
    const target = new Date(countdownBoxes.dataset.target).getTime();

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = Math.max(target - now, 0);

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      document.getElementById("days").innerText = days;
      document.getElementById("hours").innerText = hours;
      document.getElementById("minutes").innerText = minutes;
      document.getElementById("seconds").innerText = seconds;
    }

    // Update every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
  }
</script>





</body>
</html>