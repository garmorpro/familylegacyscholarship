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
    <title>Application - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">
    <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">

        <div class="card-body">

            <h2 class="text-center">
                Eligibility & Selection Criteria
            </h2>
            <p class="text-center">Please review these requirements carefully before applying.</p>

            <h4>
                Eligibility Requirements
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <ul>
                <li class="mb-3">
                    Must be a graduating senior of Battery Creek High School in the current academic year
                </li>
                <li class="mb-3">
                    Must be planning to attend an approved post-secondary institution, including:
                    <ul>
                        <li>
                            College or university (2-year or 4-year programs)
                        </li>
                        <li>
                            Trade school or technical college
                        </li>
                        <li>
                            Vocational training programs
                        </li>
                        <li>
                            Other accredited post-secondary education pathways
                        </li>
                    </ul>
                </li>
                <li class="mb-3">
                    Must complete and submit all required application materials by the stated deadline
                </li>
            </ul>

            <h4 class="pt-3">
                Selection Criteria
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

            <p>
                The Morgan Family Selection Committee will evaluate applications based on the following criteria:
            </p>
            <h5>
                Character
            </h5>
            <ul>
                <li class="mb-3">
                    Integrity and honesty in academic and personal conduct
                </li>
                <li class="mb-3">
                    Positive attitude and respectful treatment of others
                </li>
                <li class="mb-3">
                    Resilience in facing challenges
                </li>
            </ul>

            <h5>
                Leadership
            </h5>
            <ul>
                <li class="mb-3">
                    Involvement in school activities, clubs, sports, or community organizations
                </li>
                <li class="mb-3">
                    Initiative in taking on responsibilities or helping others
                </li>
                <li class="mb-3">
                    Positive influence on peers and community
                </li>
            </ul>

            <h5>
                Commitment to Growth
            </h5>
            <ul>
                <li class="mb-3">
                    Clear goals for post-secondary education and future career
                </li>
                <li class="mb-3">
                    Demonstrated effort to learn and improve
                </li>
                <li class="mb-3">
                    Genuine interest in pursuing educational opportunities
                </li>
            </ul>

            <h5>
                Financial Need (Considered)
            </h5>
            <ul class="mb-5">
                <li class="mb-3">
                    Financial circumstances will be taken into consideration but are not the primary deciding factor
                </li>
            </ul>

            <hr>

            <p class="text-muted text-center" style="font-size: 14px;">
            The Selection Committee uses a holistic, criteria-based evaluation process. All qualified applicants will be considered fairly and respectfully.
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