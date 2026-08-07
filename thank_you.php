<?php
require_once 'app/db.php';
require_once 'path.php';

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

    <link rel="stylesheet" href="assets/css/styles.css?v=11.2.0">
    <title>Thank You - Morgan Legacy Scholarship</title>
    <style>
        .thankyou-card { background: #fff; border-radius: 16px; border: 1px solid rgb(241,242,243); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); max-width: 560px; margin: 0 auto; }
        .thankyou-body { padding: 48px 44px; text-align: center; }
        .thankyou-icon { width: 68px; height: 68px; border-radius: 50%; background: rgba(25,135,84,0.12); color: #198754; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
        .thankyou-title { font-size: 1.7rem; font-weight: 700; color: #212529; margin-bottom: 14px; }
        .thankyou-text { font-size: 15px; color: #495057; line-height: 1.6; margin-bottom: 14px; }
        .thankyou-note { background: rgb(249,250,251); border: 1px solid #f3f3f6; border-radius: 10px; padding: 14px 18px; font-size: 13px; color: #6c757d; line-height: 1.6; text-align: left; margin: 24px 0; }
        .thankyou-note i { color: #C5A059; margin-right: 6px; }
        .thankyou-btn { display: inline-block; background: rgb(7,5,55); color: #fff; font-weight: 600; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-size: 15px; margin-top: 8px; }
        .thankyou-btn:hover { background: rgb(20,16,80); color: #fff; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>

<main class="flex-fill">
  <div class="container py-5" style="background-color: rgb(249,250,251);">

    <div class="thankyou-card">
      <div class="case-accent"></div>
      <div class="thankyou-body">
        <div class="thankyou-icon"><i class="bi bi-check-lg"></i></div>
        <div class="thankyou-title">Thank You!</div>

        <p class="thankyou-text">
            Your application for the Morgan Family Legacy Scholarship has been successfully submitted.
        </p>
        <p class="thankyou-text">
            We've sent a confirmation to your email with our review timeline for this cycle, so you know
            what to expect next. We've also reached out to your recommender to request their letter of
            recommendation on your behalf &mdash; no further action is needed from you there.
        </p>

        <div class="thankyou-note">
            <i class="bi bi-envelope-fill"></i><strong>Don't see our email?</strong> Add
            <strong>scholarship@themorganlegacy.com</strong> to your contacts or check your spam
            folder, just in case.
        </div>

        <a href="<?= BASE_URL ?>/" class="thankyou-btn">Return to Home</a>
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
