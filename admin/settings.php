<?php
require_once '../app/functions.php';
require_once '../app/require_admin.php';

require_once '../path.php';
// Ensure PDO exists
if (!isset($pdo)) {
    die("PDO connection not initialized!");
}

// Fetch all settings from DB
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];

    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

} catch (Exception $e) {
    $settings = [];
}

// Helper function to safely get a setting
function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? htmlspecialchars($settings[$key], ENT_QUOTES, 'UTF-8') : $default;
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
    <title>Settings - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


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
        <!-- Award Information -->
<div class="w-50" style="margin-bottom: 15px; position: relative;">
    <label for="award_amount" style="font-weight: 600; display: block; margin-bottom: 5px;">Award Amount ($)</label>
    
    <div style="position: relative;">
        <span style="
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #495057;
        ">$</span>
        <input type="text" id="award_amount" name="award_amount" 
               value="<?= getSetting('award_amount') ?>" 
               style="width: 100%; padding: 8px 20px 8px 24px; border-radius: 6px; border: 1px solid #ced4da;">
    </div>
</div>



        <hr>

        <h4>
            Application Period
        </h4>
        <!-- Application Period -->
<div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <div style="flex: 1; display: flex; flex-direction: column;">
        <label for="application_open" style="font-weight: 600; margin-bottom: 3px;">Open Date</label>
        <input type="date" id="application_open" name="application_open" 
               value="<?= getSetting('application_open') ?>" 
               style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
    </div>
    <div style="flex: 1; display: flex; flex-direction: column;">
        <label for="application_closed" style="font-weight: 600; margin-bottom: 3px;">Close Date</label>
        <input type="date" id="application_closed" name="application_closed" 
               value="<?= getSetting('application_closed') ?>" 
               style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
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
<div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <div style="flex: 1; display: flex; flex-direction: column;">
        <label for="review_start" style="font-weight: 600; margin-bottom: 3px;">Start Date</label>
        <input type="date" id="review_start" name="review_start" 
               value="<?= getSetting('review_start') ?>" 
               style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
    </div>
    <div style="flex: 1; display: flex; flex-direction: column;">
        <label for="review_end" style="font-weight: 600; margin-bottom: 3px;">End Date</label>
        <input type="date" id="review_end" name="review_end" 
               value="<?= getSetting('review_end') ?>" 
               style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
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
        <div class="w-50" style="margin-bottom: 15px;">
    <label for="announcement_date" style="font-weight: 600; display: block; margin-bottom: 5px;">Announcement Date</label>
    <input type="date" id="announcement_date" name="announcement_date" 
           value="<?= getSetting('announcement_date') ?>" 
           style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
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
    <input type="email" id="notification_email" name="notification_email" 
           value="<?= getSetting('notification_email') ?>" 
           placeholder="admin@domain.com" 
           style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;">
</div>

        <span class="text-muted" style="font-size: 14px;">
            Email address for receiving application notifications and updates
        </span>

        <br>
        <div class="mt-4"></div>

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