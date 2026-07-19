<?php
$currentAdminPage = basename($_SERVER['PHP_SELF']);
function admin_nav_active($pages, $current) {
    $pages = (array) $pages;
    return in_array($current, $pages, true) ? ' active' : '';
}
?>
<header>
  <nav class="navbar navbar-expand-lg border-bottom shadow-sm" style="background-color: white !important;">
    <div class="container py-3 d-flex align-items-center justify-content-between">

      <!-- Logo + Title -->
      <a href="<?= BASE_URL ?>/admin/" class="d-flex align-items-center text-decoration-none">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="" style="height:48px; margin-right: 12px;">
        <div class="d-flex flex-column">
          <span class="h5 fw-semibold mb-0" style="color:#212529;">Morgan Legacy Scholarship</span>
          <span class="text-muted" style="font-size: 11.5px; letter-spacing: .05em; text-transform: uppercase;">Admin Portal</span>
        </div>
      </a>

      <!-- Navigation Links -->
      <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-2 gap-lg-2">
          <li class="nav-item"><a class="nav-link px-2<?= admin_nav_active(['index.php', 'application_view.php'], $currentAdminPage) ?>" href="<?= BASE_URL ?>/admin/index.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link px-2<?= admin_nav_active('recipients.php', $currentAdminPage) ?>" href="<?= BASE_URL ?>/admin/recipients.php">Recipients</a></li>
          <li class="nav-item"><a class="nav-link px-2<?= admin_nav_active('settings.php', $currentAdminPage) ?>" href="<?= BASE_URL ?>/admin/settings.php">Settings</a></li>
          <li class="nav-item ms-lg-3"><a class="nav-link px-2 text-muted" href="<?= BASE_URL ?>/" target="_blank" rel="noopener">View Site <i class="bi bi-box-arrow-up-right" style="font-size:.75em;"></i></a></li>
          <li class="nav-item"><a class="nav-link px-2 text-danger" href="<?= BASE_URL ?>/admin/auth/logout.php">Log Out</a></li>
        </ul>
      </div>

      <!-- Hamburger (mobile only) -->
      <button
        class="navbar-toggler d-lg-none"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#adminNav"
        aria-controls="adminNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

    </div>
  </nav>
</header>
