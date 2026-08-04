<?php
require_once '../app/db.php';
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

require_once '../path.php';

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
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=11.2.0">
    <title>Recipients - Morgan Legacy Scholarship</title>
    <style>
        .recipient-table { width: 100%; border-collapse: collapse; }
        .recipient-table th { text-align: left; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; padding: 14px 20px; border-bottom: 1px solid #f3f3f6; background: rgb(249,250,251); }
        .recipient-table td { padding: 14px 20px; border-bottom: 1px solid #f6f6f8; vertical-align: middle; }
        .recipient-table tr:last-child td { border-bottom: none; }
        .recipient-table tr.recipient-row:hover td { background: #fafbff; }
        .recipient-avatar { width: 38px; height: 38px; border-radius: 50%; background: rgb(7,5,55); color: #C5A059; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; overflow: hidden; }
        .recipient-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .recipient-name { font-weight: 600; font-size: 14.5px; color: #212529; }
        .recipient-sub { font-size: 12.5px; color: #8a8a94; }
        .pic-pill { display: inline-block; white-space: nowrap; font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .pic-pill.yes { background: rgba(25,135,84,0.12); color: #198754; }
        .pic-pill.no { background: rgba(220,53,69,0.1); color: #dc3545; }
        .pic-pill.scheduled { background: rgba(197,160,89,0.16); color: #8a6d2e; }
        .recipient-delete-btn { background: none; border: none; color: #c9c9d1; padding: 6px 8px; border-radius: 6px; }
        .recipient-delete-btn:hover { color: #dc3545; background: rgba(220,53,69,0.08); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/admin_header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  <div class="case-accent"></div>

  <div style="padding: 28px 32px 24px;">
    <a href="<?= BASE_URL ?>/admin/" class="text-decoration-none" style="font-size: 13.5px; color: #9a9aa5; font-weight: 600;">
        <i class="bi bi-arrow-left me-1"></i> Back to application portal
    </a>

    <h3 class="mt-3 mb-1" style="font-weight: 700; font-size: 1.5rem; color: #212529;">Recipients</h3>
    <h5 class="mb-0" style="font-weight: 400; font-size: 1rem; color: #6c757d;">Manage and view all recipients</h5>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mt-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mt-3" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>
  </div>

  <div style="padding: 0 32px 32px;">
    <div class="bg-white" style="border-radius: 12px; border: 1px solid rgb(241,242,243); overflow: hidden;">
        <table class="recipient-table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Contact</th>
                    <th>Application Year</th>
                    <th>Picture</th>
                    <th>Selection Email</th>
                    <th style="width: 60px;"></th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($recipients)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">No recipients found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recipients as $rec): ?>
                    <tr class="recipient-row" style="cursor: pointer;"
                        data-recipient-id="<?= $rec['id'] ?>"
                        data-recipient-picture="<?= htmlspecialchars($rec['recipient_picture']) ?>"
                        data-recipient-school="<?= htmlspecialchars($rec['intended_school'], ENT_QUOTES, 'UTF-8') ?>"
                        data-recipient-major="<?= htmlspecialchars($rec['intended_major'], ENT_QUOTES, 'UTF-8') ?>">

                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="recipient-avatar">
                                    <?php if (!empty($rec['recipient_picture'])): ?>
                                        <img src="../uploads/recipients/<?= htmlspecialchars($rec['recipient_picture']) ?>" alt="">
                                    <?php else: ?>
                                        <?= htmlspecialchars(strtoupper(substr($rec['first_name'], 0, 1))) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="recipient-name"><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></div>
                            </div>
                        </td>

                        <td>
                            <div><?= htmlspecialchars($rec['email']) ?></div>
                            <div class="recipient-sub"><?= htmlspecialchars($rec['phone']) ?></div>
                        </td>

                        <td><?= htmlspecialchars($rec['application_year']) ?></td>

                        <td>
                            <?php if (!empty($rec['recipient_picture'])): ?>
                                <span class="pic-pill yes">Uploaded</span>
                            <?php else: ?>
                                <span class="pic-pill no">Not Yet</span>
                            <?php endif; ?>
                        </td>

                        <td onclick="event.stopPropagation()">
                            <?php if (!empty($rec['selection_email_sent_at'])): ?>
                                <span class="pic-pill yes">Sent <?= date('M j, g:ia', strtotime($rec['selection_email_sent_at'])) ?></span>
                            <?php elseif (!empty($rec['selection_email_scheduled_at'])): ?>
                                <span class="pic-pill scheduled">Scheduled <?= date('M j, g:ia', strtotime($rec['selection_email_scheduled_at'])) ?></span>
                            <?php else: ?>
                                <span class="pic-pill no">Not scheduled</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-end">
                            <button type="button" class="recipient-delete-btn"
                                    data-recipient-id="<?= $rec['id'] ?>"
                                    data-recipient-name="<?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    onclick="confirmDeleteRecipient(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
  </div>

</div>

<!-- Delete confirmation form -- hidden, submitted after the SweetAlert2 confirm -->
<form id="deleteRecipientForm" method="POST" action="delete_recipient.php" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="deleteRecipientId">
</form>

<!-- Upload Picture Modal (single instance, reused for whichever row triggered it) -->
<div class="modal fade" id="uploadPictureModal" tabindex="-1" aria-labelledby="uploadPictureLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="uploadPictureForm" method="POST" enctype="multipart/form-data" action="upload_recipient_picture.php">
      <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
        <?= csrf_field() ?>
        <div class="modal-header" style="background: rgb(7,5,55); border: none; padding: 20px 24px;">
          <h5 class="modal-title text-white mb-0" id="uploadPictureLabel" style="font-weight: 600;">Recipient Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="padding: 28px 30px; background: #fbfbfc;">
            <input type="hidden" name="recipient_id" id="recipient_id">

            <!-- Intended school / major -- read-only, moved off the table to free up column width -->
            <div class="mb-3" style="background: #fff; border: 1px solid #ececf1; border-radius: 8px; padding: 12px 16px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; margin-bottom: 2px;">Intended School</div>
                <div id="modalRecipientSchool" style="font-size: 14.5px; color: #212529; font-weight: 600;"></div>
                <div id="modalRecipientMajor" style="font-size: 13px; color: #8a8a94;"></div>
            </div>

            <!-- Current picture preview -->
            <div class="mb-3 d-none text-center" id="currentPictureContainer">
                <img id="currentPictureImg" src="" alt="Current picture"
                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                <div class="text-muted mt-2" style="font-size: 12.5px;">Current picture</div>
            </div>

            <label for="recipient_picture" class="form-label" id="fileInputLabel" style="font-weight: 600; font-size: 14px;">Choose an image</label>
            <input type="file" class="form-control" name="recipient_picture" id="recipient_picture" accept="image/*" required
                   style="border-radius: 6px; border: 1px solid #ced4da;">
            <div class="text-muted d-none mt-2" id="replaceHint" style="font-size: 12.5px;">
                Uploading a new image will replace the current one.
            </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #ececf1; padding: 16px 24px;">
          <button type="button" class="btn" style="background: #f1f1f4; color: #495057;" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" style="background: rgb(7,5,55); color: #fff;" id="uploadButton">Upload</button>
        </div>
      </div>
    </form>
  </div>
</div>

</div>
</main>

<?php include_once ROOT_PATH . '/assets/includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
var uploadModalEl = document.getElementById('uploadPictureModal');
var uploadModal = new bootstrap.Modal(uploadModalEl);

// Row clicks open the upload modal -- but only when the click didn't
// originate on (or inside) the delete button. Handling this explicitly
// in JS, instead of relying on Bootstrap's data-bs-toggle click delegation
// plus stopPropagation() racing it, is what actually guarantees the two
// actions never both fire from a single click.
document.querySelectorAll('.recipient-row').forEach(function (row) {
    row.addEventListener('click', function (event) {
        if (event.target.closest('.recipient-delete-btn')) {
            return;
        }
        openUploadModal(
            row.getAttribute('data-recipient-id'),
            row.getAttribute('data-recipient-picture'),
            row.getAttribute('data-recipient-school'),
            row.getAttribute('data-recipient-major')
        );
    });
});

function openUploadModal(recipientId, recipientPicture, recipientSchool, recipientMajor) {
    document.getElementById('recipient_id').value = recipientId;
    document.getElementById('modalRecipientSchool').textContent = recipientSchool || '—';
    document.getElementById('modalRecipientMajor').textContent = recipientMajor || '';

    var currentPictureContainer = document.getElementById('currentPictureContainer');
    var currentPictureImg = document.getElementById('currentPictureImg');
    var fileInputLabel = document.getElementById('fileInputLabel');
    var replaceHint = document.getElementById('replaceHint');
    var fileInput = document.getElementById('recipient_picture');

    fileInput.value = ''; // clear any previously chosen file from a prior open

    if (recipientPicture) {
        // Recipient already has a picture — show it, and let uploading a
        // new one replace it.
        currentPictureImg.src = '../uploads/recipients/' + recipientPicture;
        currentPictureContainer.classList.remove('d-none');
        fileInputLabel.textContent = 'Replace image';
        replaceHint.classList.remove('d-none');
    } else {
        currentPictureContainer.classList.add('d-none');
        currentPictureImg.src = '';
        fileInputLabel.textContent = 'Choose an image';
        replaceHint.classList.add('d-none');
    }

    uploadModal.show();
}

// Deleting a recipient is permanent -- a styled, hard-to-miss confirmation
// before anything actually gets submitted.
function confirmDeleteRecipient(btn) {
    var id = btn.getAttribute('data-recipient-id');
    var name = btn.getAttribute('data-recipient-name');

    Swal.fire({
        icon: 'warning',
        title: 'Remove ' + name + '?',
        html: 'This permanently deletes this recipient record and their uploaded picture. <strong>This cannot be undone.</strong>',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        focusConfirm: false
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('deleteRecipientId').value = id;
            document.getElementById('deleteRecipientForm').submit();
        }
    });
}
</script>

</body>
</html>
