<?php
require_once '../app/db.php';
// require_once '../app/require_admin.php';

require_once '../path.php';
// Ensure PDO exists


// Fetch all recipients from DB

try {
    $recipientsStmt = $pdo->query("SELECT * FROM recipients ORDER BY application_year DESC");
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.0.0">
    <title>Recipients - Morgan Legacy Scholarship</title>
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

<!-- Top header remains white -->
  <div class="card-header bg-white shadow-sm" style="padding: 1.5rem !important; padding-bottom: 0 !important;">
    <div class="">
        <!-- Back link -->
        <a href="<?= BASE_URL ?>/admin/" class="text-decoration-none text-muted d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Back to application portal
        </a>
    </div>

  <!-- Text with padding preserved -->
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-4" style="padding: 5px 5px;">
        <!-- Left: Titles -->
        <div>
            <h3 class="mb-1" style="font-weight: 600; font-size: 1.5rem; color: #212529;">Recipients</h3>
            <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Manage and view all recipients</h5>
        </div>
    </div>


    <div class="mt-4 bg-white shadow-sm"
     style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">

    <table class="table table-hover mb-0 align-middle" id="applicationsTable">
        <thead class="table-light">
            <tr>
                <th style="width: 20px;"></th>
                <th>Applicant</th>
                <th>Contact</th>
                <th>Intended School</th>
                <th>Application Year</th>
                <th>Picture</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($recipients)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    No recipients found
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($recipients as $rec): ?>
                <tr style="cursor: pointer;"
                    data-bs-toggle="modal"
                    data-bs-target="#uploadPictureModal"
                    data-recipient-id="<?= $rec['id'] ?>"
                    data-recipient-picture="<?= htmlspecialchars($rec['recipient_picture']) ?>">

                    <!-- Upload Picture Modal -->
                    <div class="modal fade" id="uploadPictureModal" tabindex="-1" aria-labelledby="uploadPictureLabel" aria-hidden="true">
                      <div class="modal-dialog">
                        <form id="uploadPictureForm" method="POST" enctype="multipart/form-data" action="upload_recipient_picture.php">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="uploadPictureLabel">Upload Recipient Picture</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="recipient_id" id="recipient_id">

                                <!-- File input container -->
                                <div class="mb-3" id="fileInputContainer">
                                    <label for="recipient_picture" class="form-label">Choose an image</label>
                                    <input type="file" class="form-control" name="recipient_picture" id="recipient_picture" accept="image/*" required>
                                </div>

                                <!-- Message container -->
                                <div class="alert alert-info d-none" id="alreadyUploadedMessage">
                                    This recipient already has a picture uploaded.
                                </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary" id="uploadButton">Upload</button>
                            </div>
                          </div>
                        </form>
                      </div>
                    </div>


                    <script>
                    var uploadModal = document.getElementById('uploadPictureModal');
                                
                    uploadModal.addEventListener('show.bs.modal', function (event) {
                        var button = event.relatedTarget; // Row that triggered modal
                        var recipientId = button.getAttribute('data-recipient-id');
                        var recipientPicture = button.getAttribute('data-recipient-picture');
                                
                        document.getElementById('recipient_id').value = recipientId;
                                
                        var fileInputContainer = document.getElementById('fileInputContainer');
                        var alreadyUploadedMessage = document.getElementById('alreadyUploadedMessage');
                        var uploadButton = document.getElementById('uploadButton');
                                
                        if (recipientPicture) {
                            // Recipient already has a picture
                            fileInputContainer.classList.add('d-none');
                            alreadyUploadedMessage.classList.remove('d-none');
                            uploadButton.disabled = true; // disable upload
                        } else {
                            // No picture yet
                            fileInputContainer.classList.remove('d-none');
                            alreadyUploadedMessage.classList.add('d-none');
                            uploadButton.disabled = false;
                        }
                    });
                    </script>




                    <td style="width: 20px;"></td>

                    <!-- Name + GPA -->
                    <td>
                        <div class="fw-semibold">
                            <?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?>
                        </div>
                      
                    </td>

                    <!-- Contact -->
                    <td>
                        <div><?= htmlspecialchars($rec['email']) ?></div>
                        <div class="text-muted" style="font-size: 13px;">
                            <?= htmlspecialchars($rec['phone']) ?>
                        </div>
                    </td>

                    <!-- Intended School -->
                    <td>
                        <div><?= htmlspecialchars($rec['intended_school']) ?></div>
                        <div class="text-muted" style="font-size: 13px;">
                            <?= htmlspecialchars($rec['intended_major']) ?>
                        </div>
                    </td>

                    <!-- Date Submitted -->
                    <td>
                        <?= htmlspecialchars($rec['application_year']) ?>
                    </td>

                    <!-- Date Submitted -->
                    <td>
                        <?php if (!empty($rec['recipient_picture'])): ?>
                            <span class="badge rounded-pill bg-success-subtle text-success">Uploaded</span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-danger-subtle text-danger">Not Yet</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-end text-muted">
                        <i class="bi bi-chevron-right"></i>
                    </td>


                    
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

    






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