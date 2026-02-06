<header>
  <nav class="navbar navbar-expand-lg border-bottom shadow-sm" style="background-color: white !important;">
    <div class="container py-3 d-flex align-items-center justify-content-between">

      <!-- Logo + Title -->
      <div class="d-flex align-items-center">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="" style="height:75px; margin-right: 15px;">

        <div class="d-flex flex-column text-center">
          <h1 class="h4 fw-semibold mb-0">
            The Morgan Family Legacy Scholarship
          </h1>
          <p class="text-muted mb-0">
            Battery Creek High School, Beaufort, SC
          </p>
        </div>
      </div>

      <!-- Navigation Links -->
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto gap-2 gap-lg-2">
          <li class="nav-item"><a class="nav-link active px-2" href="<?= BASE_URL ?>/">Home</a></li>
          <li class="nav-item"><a class="nav-link px-2" href="<?= BASE_URL ?>/about.php">About</a></li>
          <li class="nav-item"><a class="nav-link px-2" href="<?= BASE_URL ?>/eligibility.php">Eligibility</a></li>
          <li class="nav-item"><a class="nav-link px-2" href="<?= BASE_URL ?>/application.php">Application</a></li>
          <li class="nav-item"><a class="nav-link px-2" href="<?= BASE_URL ?>/recipients.php">Recipients</a></li>
        </ul>
      </div>

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
  </nav>
</header>
