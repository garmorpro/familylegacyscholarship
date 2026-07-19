<?php
require_once 'app/db.php';
require_once 'path.php';

try {
    $recipientsStmt = $pdo->query("SELECT * FROM recipients ORDER BY application_year DESC, created_at DESC");
    $recipients = $recipientsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recipients = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/styles.css?v=<?= time() ?>">
    <title>Recipients - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">
    <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">

        <div class="card-body">

            <h2>
                <i class="bi bi-award me-2 text-primary"></i>Scholarship Recipients
            </h2>

            <?php if (empty($recipients)): ?>
                <div class="d-flex flex-column mt-4 px-5">
                    <i class="bi bi-award text-muted text-center mb-2" style="font-size: 48px;"></i>
                    <h4 class="text-center">
                        Building Our Legacy
                    </h4>
                    <p class="text-center">
                        We're excited to announce our first scholarship recipient soon. This page will feature the outstanding students who have been awarded the Morgan Family Legacy Scholarship.
                    </p>
                    <p class="text-muted text-center mb-5" style="font-size: 14px;">
                        Check back after our first award cycle is complete to learn about our recipients and their educational journeys.
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-4 mt-2 mb-4 justify-content-center">
                    <?php foreach ($recipients as $rec): ?>
                        <?php
                            $recName = trim(($rec['first_name'] ?? '') . ' ' . ($rec['last_name'] ?? ''));
                            $initials = strtoupper(substr($rec['first_name'] ?? '', 0, 1) . substr($rec['last_name'] ?? '', 0, 1));
                            $hasPhoto = !empty($rec['recipient_picture']);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm" style="border-radius: 14px; border: 1px solid rgb(241,242,243); overflow: hidden;">
                                <div style="position: relative; width: 100%; aspect-ratio: 4 / 5; background: rgb(7,5,55);">
                                    <?php if ($hasPhoto): ?>
                                        <img src="uploads/recipients/<?= htmlspecialchars($rec['recipient_picture']) ?>"
                                             alt="<?= htmlspecialchars($recName) ?>"
                                             loading="lazy"
                                             style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                            <span style="color: #fff; font-size: 56px; font-weight: 600; letter-spacing: 1px;">
                                                <?= htmlspecialchars($initials ?: '?') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($rec['application_year'])): ?>
                                        <span style="position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.94); color: rgb(7,5,55); font-size: 12px; font-weight: 600; padding: 4px 11px; border-radius: 999px; letter-spacing: .02em;">
                                            Class of <?= htmlspecialchars($rec['application_year']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body text-center" style="padding: 22px 20px;">
                                    <h5 class="fw-semibold mb-0" style="font-size: 19px; color: #212529;"><?= htmlspecialchars($recName) ?></h5>
                                    <div style="width: 34px; height: 3px; background: rgb(197,160,89); margin: 10px auto 12px; border-radius: 2px;"></div>
                                    <?php if (!empty($rec['intended_school'])): ?>
                                        <div class="fw-medium" style="font-size: 14.5px; color: #212529;"><?= htmlspecialchars($rec['intended_school']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($rec['intended_major'])): ?>
                                        <div class="text-muted" style="font-size: 13px;"><?= htmlspecialchars($rec['intended_major']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr>
            <p class="text-muted text-center" style="font-size: 14px;">
                Recipients are selected annually based on character, leadership, commitment to growth, and financial need. All recipient information is shared with their consent.
            </p>
        
            
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