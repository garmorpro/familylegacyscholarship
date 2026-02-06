<?php
require_once 'app/db.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/styles.css?v=10.0.2">
    <title>Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<header>
  <nav class="navbar navbar-expand-lg border-bottom shadow-sm" style="background-color: white !important;">
    <div class="container py-3">

      <!-- Title + Hamburger -->
      <div class="d-flex flex-column w-100">
        <!-- Top row: h1 + hamburger -->
        <div class="d-flex align-items-center justify-content-between w-100">
          <h1 class="h4 fw-semibold mb-1">
            The Morgan Family Legacy Scholarship
          </h1>

          <!-- Hamburger (mobile only) -->
          <button
            class="navbar-toggler d-lg-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNav"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="Toggle navigation"
          >
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>

        <!-- Subtitle -->
        <p class="text-muted mb-0" style="margin-left: 55px;">
          Battery Creek High School, Beaufort, SC
        </p>
      </div>

      <!-- Navigation Links -->
      <div class="collapse navbar-collapse mt-3 mt-lg-0" id="mainNav">
        <ul class="navbar-nav ms-auto gap-2 gap-lg-3">
          <li class="nav-item"><a class="nav-link active px-3" href="/index.html">Home</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="/about.html">About</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="/eligibility.html">Eligibility</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="/application.html">Application</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="/recipients.html">Recipients</a></li>
        </ul>
      </div>

    </div>
  </nav>
</header>


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
$applicationCloseYear = date("Y", strtotime($applicationClose)); // e.g., "2026"
?>


  <!-- Text with padding preserved -->
  <div class="card-body text-center">
    <h2 class="card-title">Supporting Excellence at Battery Creek High School</h2>
    <p class="card-text">Honoring character, leadership, and a commitment to growth for graduating seniors pursuing higher education.</p>
    <div class="mb-2">
      <?php
$today = date('Y-m-d');
?>

<?php if ($today >= $applicationOpen && $today <= $applicationClose): ?>
  <span class="badge rounded-pill mb-1"
        style="background-color: rgb(226,251,232); color: rgb(43,101,54); font-weight: 400 !important;">
    <i class="bi bi-check2-circle me-1"></i>
    Applications Now Open
  </span>
<?php else: ?>
  <span class="badge rounded-pill mb-1"
        style="background-color: rgb(253,235,235); color: rgb(153,27,27); font-weight: 400 !important;">
    <i class="bi bi-x-circle me-1"></i>
    Application Closed
  </span>
<?php endif; ?>

      <br>
      <p class="text-muted" style="font-size: 12px;">
        Class of <?= $applicationCloseYear ?>
      </p>
    </div>

    <div class="card countdown-card mx-auto" style="background-color: rgb(7,5,55); color: white; border: none; padding: .5rem !important;">
  <div class="card-body text-center">
    <p style="font-size: 14px;">
      <i class="bi bi-clock"></i> Time remaining to submit your application:
    </p>

    <div id="countdown-container" class="d-flex gap-2 flex-wrap justify-content-center position-relative">

  <!-- Countdown boxes -->
  <div id="countdown-boxes" class="d-flex gap-2 flex-wrap justify-content-center">
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

  <!-- Closed / not open message -->
  <div id="countdown-message" class="text-center" style="
      display: none;
      font-size: 20px;
      font-weight: 600;
      color: rgb(220,53,69); /* red color */
      width: 100%;
    ">
    Application is closed
  </div>

</div>

  </div>
</div>

<div class="row g-3 mb-3">
  <!-- Card 1: Award Amount -->
  <div class="col-12 col-md-6">
    <div class="card mb-0" style="border: none !important; height: 75px;">
      <div class="card-body">
        <h4 class="card-title" style="font-size: 18px;">
          <i class="bi bi-currency-dollar me-2"  style="color: rgb(45,92,242);"></i>Award Amount
        </h4>
        <p class="card-text text-muted" style="font-size: 16px !important;">
          $<?= number_format(getSetting('award_amount', 0)) ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Card 2: Application Deadline -->
  <?php
$applicationOpen = getSetting('application_open');   // e.g., 2026-02-15
$applicationClose = getSetting('application_closed'); // e.g., 2026-04-15
$today = date('Y-m-d');

if ($today < $applicationOpen) {
    $label = "Application Opens";
    $dateToShow = $applicationOpen;
} elseif ($today > $applicationClose) {
    $label = "Application Opens";
    $dateToShow = $applicationOpen; // you could also say "Applications closed" if preferred
} else {
    $label = "Application Deadline";
    $dateToShow = $applicationClose;
}
?>

<div class="col-12 col-md-6">
    <div class="card mt-0" style="border: none !important; height: 75px;">
      <div class="card-body">
        <h4 class="card-title" style="font-size: 18px;">
          <i class="bi bi-calendar2 me-2" style="color: rgb(45,92,242);"></i><?= $label ?>
        </h4>
        <p class="card-text text-muted" style="font-size: 16px !important;">
          <?= date("F j, Y", strtotime($dateToShow)) ?>
        </p>
      </div>
    </div>
</div>

</div>


<?php if ($today >= $applicationOpen && $today <= $applicationClose) {
?>
    <a href="/application-form.php"
       class="btn mt-4"
       style="background-color: rgb(7, 5, 55); color:white; font-size: 18px !important;">
        <i class="bi bi-file-earmark-text me-2"></i>
        Start Your Application
    </a>
<?php
}
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

  <a href="/eligibility.html" class="card-link text-decoration-none">View Full Eligibility Requirements <i class="bi bi-arrow-right-short"></i></a>
  </div>
</div>



<!-- next section -->

<div class="row g-3">
  <!-- Card 1 -->
  <div class="col-12 col-md-4">
    <a href="/about.html" style="text-decoration: none; color: inherit;">
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
    <a href="/eligibility.html" style="text-decoration: none; color: inherit;">
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
    <a href="/application.html" style="text-decoration: none; color: inherit;">
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

<footer class="bg-white border-top shadow-sm mt-5 pt-4 pb-4 text-center">
  <div class="container">

    <!-- Contact -->
    <div class="mb-3">
      <p class="mb-0" style="font-size: 14px;">
        <strong>Contact:</strong> 
        <a href="mailto:scholarship@morganlegacy.com" class="text-decoration-none text-dark footer-link">scholarship@morganlegacy.com</a>
      </p>
    </div>

    <hr style="opacity: 0.2;">

    <!-- Disclaimer -->
    <div class="mb-3">
      <p style="font-size: 13px; line-height: 1.5; margin: 0;">
        <strong>Disclaimer:</strong> The Morgan Family Legacy Scholarship is a privately funded family scholarship and is not affiliated with Battery Creek High School or the Beaufort County School District.
      </p>
    </div>

    <hr style="opacity: 0.2;">

    <!-- Privacy & Copyright -->
     <div style="font-size: 13px;">
      <a href="#" class="text-decoration-none text-dark me-3 footer-link">Privacy Policy</a>
      &copy; 2026 Morgan Family Legacy Scholarship
    </div>

  </div>
</footer>





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
  const applicationOpen = new Date("<?= $applicationOpen ?>T00:00:00").getTime();
  const applicationClose = new Date("<?= $applicationClose ?>T23:59:59").getTime();

  const countdownBoxes = document.getElementById("countdown-boxes");
  const countdownMessage = document.getElementById("countdown-message");

  function updateCountdown() {
    const now = new Date().getTime();

    if (now < applicationOpen) {
      countdownBoxes.style.display = "none";
      countdownMessage.style.display = "block";
      countdownMessage.innerText = "Application is closed";
      return;
    }

    if (now > applicationClose) {
      countdownBoxes.style.display = "none";
      countdownMessage.style.display = "block";
      countdownMessage.innerText = "Application is closed";
      return;
    }

    // Applications are open → show countdown
    countdownBoxes.style.display = "flex";
    countdownMessage.style.display = "none";

    const distance = applicationClose - now;

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
</script>





</body>
</html>