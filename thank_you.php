<?php
require_once 'app/db.php';
require_once 'path.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/styles.css?v=<?= time() ?>">
    <title>Thank You - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>

<main class="flex-fill">
  <div class="container py-5" style="background-color: rgb(249,250,251);">
    <div class="row justify-content-center">
      <div class="col-md-8 text-center">

        <!-- Thank You Message -->
        <div class="card shadow-sm p-5">
          <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          <h2 class="mt-3">Thank You!</h2>
          <p class="lead mt-3">
            Your application for the Morgan Family Legacy Scholarship has been successfully submitted.
          </p>
          <p>
            We will review your submission and contact you if any additional information is required.
          </p>
          <p>
            * NOTE: Be sure to add our email address (scholarship@themorganlegacy.com) to your allow list (or just add it to your address book), otherwise our email might get lost in your spam folder.
          </p>
          <a href="<?= BASE_URL ?>/" class="btn btn-primary mt-3">Return to Home</a>
        </div>

      </div>
    </div>
  </div>
</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
