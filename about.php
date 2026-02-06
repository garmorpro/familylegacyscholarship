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
    <title>About - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  <!-- Image fills top corners, edge-to-edge -->
   <img src="assets/images/family_beach_photo.png"
     class="card-img-top home-main-img"
     alt="..."
     style="display:block; width:100%; height:200px; object-fit: cover; object-position: 20% 60% !important; border-top-left-radius: 12px; border-top-right-radius: 12px; margin:0; padding:0;">
  



  <!-- Text with padding preserved -->
  <div class="card-body">
    <h2 class="card-title text-center">About The Morgan Family Legacy Scholarship</h2>
    <!-- <hr class="mx-auto" style="width: 50%; height: 5px; background-color: #3b3b3b; border: none; opacity: 1;"> -->
     <hr>

    <h4 class="pt-3 pb-3">Our Connection to Battery Creek</h4>
    <p>
        The Morgan family has deep roots in the Beaufort community and Battery Creek High School. Our family members attended Battery Creek, participated in its programs, and witnessed firsthand the dedication of its teachers and the potential of its students.
    </p>
    <p>
        Battery Creek High School shaped who we are, and we are committed to giving back to the students who walk its halls today.
    </p>

    <h4 class="pt-3 pb-3">
        Why We Created This Scholarship
    </h4>
    <p>
        We believe that every student deserves the opportunity to pursue their dreams beyond high school. This scholarship was created to support graduating seniors who demonstrate not only academic achievement, but also the character, leadership, and determination that will help them succeed in college, trade school, or other post-secondary paths.
    </p>
    <p>
        We're looking for students who are committed to personal growth, who contribute positively to their community, and who are ready to make the most of their next chapter.
    </p>
    <h4 class="pt-3 pb-3">
        Our Commitment
    </h4>
    <p>
        The Morgan Family Legacy Scholarship is an annual commitment. Each year, we will award this scholarship to a deserving Battery Creek graduating senior to help them pursue their educational goals.
    </p>
    <p class="pb-3">
        This is more than a one-time gift—it's a lasting investment in the future of our community and the students who will shape it.
    </p>

    <!-- <hr>

    <p class="text-muted" style="font-size: 14px;">
        The Morgan Family Legacy Scholarship is a privately funded scholarship and is not affiliated with Battery Creek High School or the Beaufort County School District.
    </p> -->

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