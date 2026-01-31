<?php
require_once '../app/functions.php';

/**
 * Status counts
 */
try {
    $countsStmt = $pdo->query("
        SELECT application_status, COUNT(*) AS total
        FROM scholarship_applications
        GROUP BY application_status
    ");

    $statusCounts = [
        'submitted' => 0,
        'reviewed' => 0,
        'selected' => 0
    ];

    while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($statusCounts[$row['application_status']])) {
            $statusCounts[$row['application_status']] = (int) $row['total'];
        }
    }
} catch (Exception $e) {
    $statusCounts = [
        'submitted' => 0,
        'reviewed' => 0,
        'selected' => 0
    ];
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
        ORDER BY submitted_at DESC
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
    <h3>
        Application Portal
    </h3>
    <h5>
        Review and manage scholarship applications
    </h5>

    <div class="row g-3 mb-4">

    <!-- Open Applications -->
    <div class="col-md-4">
        <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
             style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

            <div class="d-flex align-items-center">
                <div class="me-3 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(13,110,253,0.1);">
                    <i class="bi bi-inbox-fill text-primary"></i>
                </div>

                <div>
                    <div class="fw-semibold">Open</div>
                    <div class="text-muted" style="font-size: 13px;">
                        Awaiting review
                    </div>
                </div>
            </div>

            <div class="fs-4 fw-bold text-primary">
                <?= $statusCounts['submitted'] ?>
            </div>
        </div>
    </div>

    <!-- Reviewed Applications -->
    <div class="col-md-4">
        <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
             style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

            <div class="d-flex align-items-center">
                <div class="me-3 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(108,117,125,0.15);">
                    <i class="bi bi-eye-fill text-secondary"></i>
                </div>

                <div>
                    <div class="fw-semibold">Reviewed</div>
                    <div class="text-muted" style="font-size: 13px;">
                        Initial review complete
                    </div>
                </div>
            </div>

            <div class="fs-4 fw-bold text-secondary">
                <?= $statusCounts['reviewed'] ?>
            </div>
        </div>
    </div>

    <!-- Selected for Further Review -->
    <div class="col-md-4">
        <div class="d-flex align-items-center justify-content-between p-3 bg-white shadow-sm"
             style="border-radius: 12px; border: 1px solid rgb(241,242,243);">

            <div class="d-flex align-items-center">
                <div class="me-3 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px; border-radius: 10px; background-color: rgba(25,135,84,0.15);">
                    <i class="bi bi-check-circle-fill text-success"></i>
                </div>

                <div>
                    <div class="fw-semibold">Selected</div>
                    <div class="text-muted" style="font-size: 13px;">
                        Further review
                    </div>
                </div>
            </div>

            <div class="fs-4 fw-bold text-success">
                <?= $statusCounts['selected'] ?>
            </div>
        </div>
    </div>

</div>



<!-- TABLE -->
    <div class="mt-4 bg-white shadow-sm"
     style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">

    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 40px;"></th>
                <th>Applicant</th>
                <th>Contact</th>
                <th>Intended School</th>
                <th>Submitted</th>
                <th>Status</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($applications)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    No applications found
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <tr style="cursor: pointer;"
                    onclick="window.location.href='application_view.php?id=<?= $app['id'] ?>'">

                    <!-- Checkbox -->
                    <td>
                        <input type="checkbox" class="form-check-input">
                    </td>

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

                    <!-- Status -->
                    <td>
                        <span class="badge
                            <?php
                                echo match ($app['application_status']) {
                                    'submitted' => 'bg-primary-subtle text-primary',
                                    'reviewed'  => 'bg-secondary-subtle text-secondary',
                                    'selected'  => 'bg-success-subtle text-success',
                                    default     => 'bg-light text-dark'
                                };
                            ?>">
                            <?= ucfirst($app['application_status']) ?>
                        </span>
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



<!-- END TABLE -->

    
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