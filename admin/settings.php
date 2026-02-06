<?php
require_once '../app/functions.php';

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
        'selected'  => 0
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
        'selected'  => 0
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


<header>
  <nav class="navbar navbar-expand-lg border-bottom shadow-sm" style="background-color: white !important;">
    <div class="container py-3">

      <!-- Title + Hamburger -->
      <div class="d-flex flex-column w-100">
        <!-- Top row: h1 + hamburger -->
        <div class="d-flex align-items-center justify-content-between w-100">
          <h1 class="h4 fw-semibold mb-1">
            Morgan Family Legacy Scholarship
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
        <p class="text-muted mb-0">
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

  <!-- Text with padding preserved -->
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-4" style="padding: 15px 20px;">
        <!-- Left: Titles -->
        <div>
            <h3 class="mb-1" style="font-weight: 600; font-size: 1.5rem; color: #212529;">Admin Settings</h3>
            <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Manage application periods, award amounts, and timeline dates</h5>
        </div>
    </div>

    <!-- Left Border Alert -->
    <div style="
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 5px solid rgb(62,163,45);
        background-color: rgb(242,253,244);
        padding: 15px 20px;
        border-radius: 12px;
        color: #212529;
        font-size: 14px;
        margin-bottom: 20px;
    ">
        <div style="
            flex-shrink: 0;
            font-size: 20px;
            color: rgb(62,163,75);
            line-height: 1;
        ">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <div>
            <div style="font-weight: 600; font-size: 15px; margin-bottom: 3px; color: rgb(38,82,47) !important;">Applications are currently open</div>
            <div style="font-weight: 400; font-size: 14px; color: rgb(51,128,63) !important;">
                Students can submit applications through the website
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="save_settings.php" style="margin-top: 20px;">
        <!-- Award Information -->
         <h4>
            Award Information
         </h4>
        <div style="margin-bottom: 15px;">
            <label for="award_amount" style="font-weight: 600; display: block; margin-bottom: 5px;">Award Amount ($)</label>
            <input type="number" step="0.01" id="award_amount" name="award_amount" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
        </div>

        <hr>

        <h4>
            Application Period
        </h4>
        <!-- Application Period -->
        <div style="margin-bottom: 15px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px;">Application Period</label>
            <div style="display: flex; gap: 10px;">
                <input type="date" id="application_open" name="application_open" style="flex: 1; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
                <input type="date" id="application_closed" name="application_closed" style="flex: 1; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
            </div>
        </div>
        <span class="text-muted" style="font-size: 14px;">
            Set the dates when students can submit applications
        </span>

        <hr>

        <h4>
            Review Period
        </h4>

        <!-- Review Period -->
        <div style="margin-bottom: 15px;">
    <div style="display: flex; gap: 10px;">
        <!-- Start Date -->
        <div style="flex: 1; display: flex; flex-direction: column;">
            <label for="review_start" style="font-weight: 500; margin-bottom: 3px;">Start Date</label>
            <input type="date" id="review_start" name="review_start" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
        </div>

        <!-- End Date -->
        <div style="flex: 1; display: flex; flex-direction: column;">
            <label for="review_end" style="font-weight: 500; margin-bottom: 3px;">End Date</label>
            <input type="date" id="review_end" name="review_end" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
        </div>
    </div>
</div>

        <span class="text-muted" style="font-size: 14px;">
            Estimated period for reviewing and evaluating applications
        </span>

        <hr>

        <h4>
            Recipient Announcement
        </h4>

        <!-- Recipient Announcement -->
        <div class="w-25" style="margin-bottom: 15px;">
            <label for="announcement_date" style="font-weight: 600; display: block; margin-bottom: 5px;">Announcement Date</label>
            <input type="date" id="announcement_date" name="announcement_date" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
        </div>

        <span class="text-muted" style="font-size: 14px;">
            Date when scholarship recipients will be announced
        </span>

        <hr>

        <h4>
            Notification Settings
        </h4>

        <!-- Notification Email -->
        <div style="margin-bottom: 20px;">
            <label for="notification_email" style="font-weight: 600; display: block; margin-bottom: 5px;">Notification Email</label>
            <input type="email" id="notification_email" name="notification_email" placeholder="admin@domain.com" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
        </div>

        <span class="text-muted" style="font-size: 14px;">
            Email address for receiving application notifications and updates
        </span>

        <!-- Submit Button -->
        <button type="submit" style="
            padding: 10px 20px;
            background-color: #0d6efd;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        " onmouseover="this.style.backgroundColor='#0b5ed7'" onmouseout="this.style.backgroundColor='#0d6efd'">
            Save Settings
        </button>
    </form>

  </div>
</div>



</div>
</main>

<footer class="bg-white border-top shadow-sm mt-5 pt-4 pb-4">
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




</body>
</html>