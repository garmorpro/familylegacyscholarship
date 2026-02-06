<header>
  <nav class="navbar navbar-expand-lg border-bottom shadow-sm" style="background-color: white !important;">
    <div class="container py-3">

      <!-- Title + Hamburger -->
      <div class="d-flex flex-column w-100">
        <!-- Top row: h1 + hamburger -->
        <div class="d-flex align-items-center justify-content-between w-100">
            <img src="<?= ROOT_PATH ?>/assets/images/logo.png" alt="">
          <h1 class="h4 fw-semibold mb-1">
            The Morgan Family Legacy Scholarship
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
        <p class="text-muted mb-0" style="margin-left: 55px;">
          Battery Creek High School, Beaufort, SC
        </p>
      </div>

      <!-- Navigation Links -->
      <div class="collapse navbar-collapse mt-3 mt-lg-0" id="mainNav">
        <ul class="navbar-nav ms-auto gap-2 gap-lg-3">
          <li class="nav-item"><a class="nav-link active px-3" href="<?= BASE_URL ?>/">Home</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="<?= BASE_URL ?>/about.php">About</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="<?= BASE_URL ?>/eligibility.php">Eligibility</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="<?= BASE_URL ?>/application.php">Application</a></li>
          <li class="nav-item"><a class="nav-link px-3" href="<?= BASE_URL ?>/recipients.php">Recipients</a></li>
        </ul>
      </div>

    </div>
  </nav>
</header>