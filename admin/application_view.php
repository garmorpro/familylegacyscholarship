<?php
require_once '../app/db.php';

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

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; border-color: rgb(241,242,243) !important; padding: 0 !important;">
  
  <!-- Top header remains white -->
  <div class="card-header bg-white shadow-sm" style="padding: 1.5rem !important; padding-bottom: 0 !important;">
    <div class="mb-3">
        <!-- Back link -->
        <a href="application_portal.php" class="text-decoration-none text-muted d-inline-flex align-items-center">
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
?>

<?php if ($application): ?>

<div class="row align-items-center py-3">
    <!-- Left: Name + Major/School -->
    <div class="col-md-6">
        <h2 class="fw-semibold mb-1">
            <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
        </h2>
        <div class="text-muted">
            <?= htmlspecialchars($application['intended_major']) ?> &bull; <?= htmlspecialchars($application['intended_school']) ?>
        </div>
    </div>

    <!-- Right: Submission Date & Status -->
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
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
                        'selected'  => 'bg-success-subtle text-success',
                        default     => 'bg-light text-dark'
                    };
                ?>">
                <?= ucfirst($application['application_status']) ?>
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
                        <i class="bi bi-envelope me-2"></i> Email
                    </div>
                    <div><?= htmlspecialchars($application['email'] ?? 'N/A') ?></div>
                </div>
                <div class="mb-0">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-telephone me-2"></i> Phone
                    </div>
                    <div><?= htmlspecialchars($application['phone'] ?? 'N/A') ?></div>
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
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title fw-semibold mb-0">Recommendation</h5>
                    <div class="d-flex gap-1">
                        <i class="bi bi-eye-fill text-success" title="Completed"></i>
                        <i class="bi bi-clock-fill text-secondary" title="Pending"></i>
                        <i class="bi bi-send-fill text-primary" title="Not Sent"></i>
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-muted">Recommender</span> <br> <span class="fw-semibold"><?= htmlspecialchars($application['recommender_name'] ?? 'N/A') ?></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted">Relationship</span> <br> <span class="fw-semibold"><?= htmlspecialchars($application['recommender_relationship'] ?? 'N/A') ?></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted">Email</span> <br> 
                    <span class="fw-semibold">
                        <a href="mailto:<?= htmlspecialchars($application['recommender_email'] ?? '') ?>">
                            <?= htmlspecialchars($application['recommender_email'] ?? 'N/A') ?>
                        </a>
                    </span>
                </div>
                <?php
                    $status = strtolower($application['recommender_status'] ?? '');
                    switch ($status) {
                        case 'completed': $badgeClass='bg-success'; $badgeText='Completed'; break;
                        case 'sent': $badgeClass='bg-primary'; $badgeText='Sent'; break;
                        case 'not sent':
                        case '': $badgeClass='bg-secondary'; $badgeText='Not Sent'; break;
                        default: $badgeClass='bg-secondary'; $badgeText=htmlspecialchars($application['recommender_status']);
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
            <div class="card-header bg-white d-flex align-items-center gap-2 pt-3 ps-3" style="border: none !important;">
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
            <div class="card-header bg-white d-flex align-items-center gap-2" style="border: none !important;">
                <div class="d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: #198754; border-radius: 4px;">
                    <i class="bi bi-award text-white"></i>
                </div>
                <h5 class="mb-0 fw-semibold">Activities & Leadership</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-semibold mb-1">Extracurricular Activities:</div>
                    <div><?= nl2br(htmlspecialchars($application['extracurricular_activities'] ?? 'N/A')) ?></div>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="fw-semibold mb-1">Leadership Roles:</div>
                    <div><?= nl2br(htmlspecialchars($application['leadership_roles'] ?? 'N/A')) ?></div>
                </div>
                <hr>
                <div>
                    <div class="fw-semibold mb-1">Community Service:</div>
                    <div><?= nl2br(htmlspecialchars($application['community_service'] ?? 'N/A')) ?></div>
                </div>
            </div>
        </div>

        <!-- Essay Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-header bg-white d-flex align-items-center gap-2" style="border: none !important;">
                <div class="d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: #0d6efd; border-radius: 4px;">
                    <i class="bi bi-file-earmark-text text-white"></i>
                </div>
                <h5 class="mb-0 fw-semibold">Essay</h5>
            </div>
            <div class="card-body">
                <?php
                    $essayText = $application['essay'] ?? '';
                    $wordCount = str_word_count($essayText);
                ?>
                <div class="mb-2"><span class="fw-semibold">Word Count:</span> <?= $wordCount ?></div>
                <div><?= nl2br(htmlspecialchars($essayText)) ?></div>
            </div>
        </div>

        <!-- Additional Details Card -->
        <div class="card mb-3 shadow-sm" style="border-radius: 12px; padding: 0 !important; border: 0 !important;">
            <div class="card-header bg-white d-flex align-items-center gap-2" style="border: none !important;">
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
                    <div><?= nl2br(htmlspecialchars($application['additional_notes'] ?? 'N/A')) ?></div>
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