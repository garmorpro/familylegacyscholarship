<?php
session_start();
require_once 'app/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
}

require_once 'app/functions.php';
require_once 'app/spam_protection.php';
require_once 'app/applicant_mailer.php';
require_once 'path.php';

// Server-side timestamp of when the form was actually rendered, used
// below to reject submissions that arrive faster than a human could
// plausibly have filled the whole thing out. Only (re)set on a fresh
// GET load, not on POST, so a slow/failed submission retry isn't
// penalized for taking a while.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['app_form_started_at'] = time();
}

// Pull the essay prompt from Settings, so admin can change it without a
// code change. Falls back to a sensible default if it's never been set.
try {
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
}

function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? htmlspecialchars($settings[$key], ENT_QUOTES, 'UTF-8') : $default;
}

const DEFAULT_ESSAY_PROMPT = 'In 500–750 words, please tell us about yourself, your goals, and what makes you a strong candidate for this scholarship.';

// Handles this form's own submission -- this used to live in app/functions.php
// as an unconditional top-level "if POST" block, which meant it fired on
// *any* POST to *any* page that happened to require that file (the
// recommendation letter page, committee review, admin settings, admin
// application view all require it too), hijacking their unrelated
// submissions with this form's validation and insert logic. Now it only
// runs here, where it actually belongs.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Honeypot: a bot that blindly fills every field trips this
    // off-screen field real visitors never see. Pretend success without
    // actually saving anything, rather than giving the bot any signal
    // it was caught.
    if (is_honeypot_filled()) {
        error_log("application-form.php: honeypot triggered from " . get_client_ip());
        header("Location: thank_you.php");
        exit();
    }

    if (submission_too_fast('app_form_started_at')) {
        echo "That was fast! Please take a moment to review your application before submitting.";
        exit();
    }

    if (is_rate_limited($pdo, 'application_form')) {
        echo "Too many submissions from your network recently. Please try again in a little while.";
        exit();
    }
    record_submission_attempt($pdo, 'application_form');

    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'expected_graduation_year' => $_POST['expected_graduation_year'] ?? '',
        'gpa' => $_POST['gpa'] ?? '',
        'institution_type' => $_POST['institution_type'] ?? '',
        'intended_school' => $_POST['intended_school'] ?? '',
        'intended_major' => $_POST['intended_major'] ?? '',
        'extracurricular' => $_POST['extracurricular'] ?? '',
        'leadership' => $_POST['leadership'] ?? '',
        'community_service' => $_POST['community_service'] ?? '',
        'essay' => $_POST['essay'] ?? '',
        'recommender_name' => $_POST['recommender_name'] ?? '',
        'recommender_email' => $_POST['recommender_email'] ?? '',
        'recommender_relationship' => $_POST['recommender_relationship'] ?? '',
        'financial_need' => $_POST['financial_need'] ?? '',
        'additional_information' => $_POST['additional_information'] ?? ''
    ];

    // The form marks these required client-side, but that alone isn't
    // trustworthy -- a browser quirk, autofill, or a non-browser client can
    // still submit them empty. expected_graduation_year and gpa in
    // particular map to strictly-typed integer/numeric DB columns, and an
    // empty string there fails as a raw, unfriendly PDOException rather
    // than a normal validation message. Catch that here first.
    $requiredFields = [
        'first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email address',
        'phone' => 'Phone number', 'expected_graduation_year' => 'Expected graduation year',
        'gpa' => 'Current GPA', 'institution_type' => 'Institution type',
        'intended_school' => 'Intended school', 'intended_major' => 'Intended major',
        'extracurricular' => 'Extracurricular activities', 'leadership' => 'Leadership experience',
        'community_service' => 'Community service', 'essay' => 'Essay',
        'recommender_name' => "Recommender's name", 'recommender_email' => "Recommender's email",
        'recommender_relationship' => "Recommender's relationship",
    ];
    $missing = [];
    foreach ($requiredFields as $key => $label) {
        if (trim((string) $data[$key]) === '') {
            $missing[] = $label;
        }
    }

    if ($missing) {
        echo "Please fill out: " . implode(', ', $missing) . ".";
        exit();
    }
    if (!ctype_digit(trim($data['expected_graduation_year']))) {
        echo "Expected graduation year must be a whole number (e.g. " . (date('Y') + 3) . ").";
        exit();
    }
    // Weighted GPAs can legitimately run higher than the traditional 4.0
    // unweighted scale, but a bare is_numeric() check would still accept
    // nonsense like -5 or 999. 0-6.0 comfortably covers any real weighted
    // scale without being so strict it rejects a valid submission.
    if (!is_numeric($data['gpa']) || (float) $data['gpa'] < 0 || (float) $data['gpa'] > 6.0) {
        echo "Current GPA must be a number between 0 and 6.0.";
        exit();
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email address.";
        exit();
    }
    if (!filter_var($data['recommender_email'], FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email address for your recommender.";
        exit();
    }

    try {
        $result = insert_application_with_recommendation($pdo, $data);

        // Best-effort: email the recommender immediately. If this fails
        // (bad address, SMTP hiccup), the recommendation stays 'not_sent'
        // and gets picked up later by the send_not_sent_recommendations.php
        // cron job as a fallback — it must never block the applicant's
        // submission, since their data is already safely saved.
        try {
            send_recommendation_request_email($pdo, $config, $result['recommendation_id']);
        } catch (Throwable $e) {
            error_log("Auto-send recommendation email failed: " . $e->getMessage());
        }

        // Best-effort, same reasoning as above: lets the applicant know
        // their application was received and when review starts, but a
        // failure here must never block the submission itself.
        try {
            send_application_received_email(
                $config,
                $data['email'],
                $data['first_name'],
                $settings['review_start'] ?? null,
                $settings['review_end'] ?? null
            );
        } catch (Throwable $e) {
            error_log("Auto-send application-received email failed: " . $e->getMessage());
        }

        header("Location: thank_you.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error submitting application: " . $e->getMessage());
        echo "Sorry, something went wrong submitting your application. Please try again, and contact us if the problem continues.";
        exit();
    }
}
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

    <link rel="stylesheet" href="assets/css/styles.css?v=<?= time() ?>">
    <title>Application Form - Morgan Legacy Scholarship</title>
    <style>
        /* ---------- Submission loading overlay ---------- */
        #submit-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(120% 120% at 50% 0%, #1d1b4d 0%, #070537 60%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 2000;
        }
        #submit-overlay .panel {
            width: 100%;
            max-width: 380px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        #submit-overlay .crest {
            height: 56px;
            width: auto;
            margin-bottom: 22px;
        }
        #submit-overlay .spinner {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 3px solid rgba(197,160,89,0.22);
            border-top-color: #C5A059;
            animation: submit-spin 1s linear infinite;
            margin-bottom: 26px;
        }
        @keyframes submit-spin { to { transform: rotate(360deg); } }
        #submit-overlay h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 8px;
            color: #ECEDF6;
            text-wrap: balance;
        }
        #submit-overlay .sub {
            font-size: 14px;
            color: #A6A8C9;
            margin: 0 0 30px;
            line-height: 1.5;
        }
        #submit-overlay .steps {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 28px;
            text-align: left;
        }
        #submit-overlay .step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            color: #A6A8C9;
            transition: background 0.3s ease, color 0.3s ease;
        }
        #submit-overlay .step.active {
            background: rgba(255,255,255,0.06);
            color: #ECEDF6;
        }
        #submit-overlay .step.done { color: #ECEDF6; }
        #submit-overlay .step-marker {
            flex: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        #submit-overlay .step.active .step-marker { border-color: #C5A059; }
        #submit-overlay .step.active .step-marker::after {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #C5A059;
            animation: submit-pulse 1.1s ease-in-out infinite;
        }
        @keyframes submit-pulse {
            0%, 100% { opacity: 0.4; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1); }
        }
        #submit-overlay .step.done .step-marker {
            border-color: #5FBE87;
            background: #5FBE87;
        }
        #submit-overlay .step.done .step-marker::after {
            content: "";
            width: 5px;
            height: 9px;
            border-right: 2px solid #070537;
            border-bottom: 2px solid #070537;
            transform: rotate(45deg) translate(-1px, -1px);
            border-radius: 0;
            background: none;
            animation: none;
        }
        #submit-overlay .footnote {
            font-size: 12.5px;
            color: #A6A8C9;
            opacity: 0.85;
        }
        @media (prefers-reduced-motion: reduce) {
            #submit-overlay * { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">

<div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
  

  <!-- Text with padding preserved -->
  <div class="card-body">
    <h4>
        Scholarship Application
    </h4>

    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Heads up:</strong> this form does not save your progress. If you navigate away or close this page before submitting, your responses will be lost. We recommend drafting your essay(s) in a separate document first, then pasting them in here before you submit.
        </div>
    </div>

    <form method="POST" action="" class="container py-4" id="applicationForm">
  <?= csrf_field() ?>

  <!-- Honeypot -- left blank by real visitors, invisible to them; a bot
       that fills every field trips it. Not display:none, since some
       bots specifically skip hidden fields -- positioned off-screen
       instead so it's still "visible" to a naive scraper. -->
  <div style="position: absolute; left: -9999px; top: -9999px; height: 0; width: 0; overflow: hidden;" aria-hidden="true">
    <label for="website">Leave this field blank</label>
    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
  </div>

  <p class="text-muted mb-3" style="font-size: 13px;"><span class="text-danger">*</span> Required</p>

  <!-- Section: Personal Information -->
  <h5>Personal Information</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="firstName" name="first_name" placeholder="John" required>
    </div>
    <div class="col-md-6">
      <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Doe" required>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
      <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" required>
    </div>
    <div class="col-md-6">
  <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
  <input type="tel" class="form-control" id="phone" name="phone" placeholder="(123) 456-7890" maxlength="14" required>
</div>

<script>
const phoneInput = document.getElementById('phone');

phoneInput.addEventListener('input', function(e) {
    let input = phoneInput.value.replace(/\D/g,''); // Remove all non-digits
    if (input.length > 10) input = input.slice(0,10); // max 10 digits

    let formatted = '';
    if (input.length > 0) {
        formatted += '(' + input.substring(0,3);
    }
    if (input.length >= 4) {
        formatted += ') ' + input.substring(3,6);
    }
    if (input.length >= 7) {
        formatted += '-' + input.substring(6,10);
    }

    phoneInput.value = formatted;
});
</script>

  </div>

  <!-- Section: School Information -->
  <h5>School Information</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="gradYear" class="form-label">Expected Graduation Year <span class="text-danger">*</span></label>
      <input type="number" class="form-control" id="gradYear" name="expected_graduation_year" placeholder="<?php echo date('Y')+3; ?>" required>
    </div>
    <div class="col-md-6">
      <label for="gpa" class="form-label">Current Weighted GPA <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="gpa" name="gpa" placeholder="4.0" required>
    </div>
  </div>

  <!-- Section: Post-Secondary Education Plans -->
  <h5>Post-Secondary Education Plans</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="institutionType" class="form-label">Type of Institution <span class="text-danger">*</span></label>
      <select class="form-select" id="institutionType" name="institution_type" required>
        <option value="">Select...</option>
        <option value="4-Year College/University">4-Year College/University</option>
        <option value="2-Year College/Community College">2-Year College/Community College</option>
        <option value="Trade School">Trade School</option>
        <option value="Technical College">Technical College</option>
        <option value="Vocational Training Program">Vocational Training Program</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="col-md-6">
      <label for="intendedSchool" class="form-label">Intended School/Institution Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="intendedSchool" name="intended_school" placeholder="School Name" required>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="intendedMajor" class="form-label">Intended Major/Program of Study <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="intendedMajor" name="intended_major" placeholder="Major/Program" required>
    </div>
  </div>

  <!-- Section: Activities & Leadership -->
  <h5>Activities & Leadership</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="extracurricular" class="form-label">Extracurricular Activities, Clubs, Sports <span class="text-danger">*</span></label>
      <textarea class="form-control" id="extracurricular" name="extracurricular" rows="2" required></textarea>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="leadership" class="form-label">Leadership Roles & Responsibilities <span class="text-danger">*</span></label>
      <textarea class="form-control" id="leadership" name="leadership" rows="2" required></textarea>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="communityService" class="form-label">Community Service & Volunteer Work <span class="text-danger">*</span></label>
      <textarea class="form-control" id="communityService" name="community_service" rows="2" required></textarea>
    </div>
  </div>

  <!-- Section: Essay -->
  <h5>Essay</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="essay" class="form-label"><?= getSetting('essay_prompt', DEFAULT_ESSAY_PROMPT) ?> <span class="text-danger">*</span></label>
      <textarea class="form-control" id="essay" rows="6" name="essay" required></textarea>
      <div class="text-end mt-1" style="font-size: 12px;">Word count: <span id="wordCount">0</span> words</div>
    </div>
  </div>

  <!-- Section: Letter of Recommendation -->
  <h5>Letter of Recommendation</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <p>Please provide contact information for one person who can speak to your character and potential (teacher, counselor, coach, employer, or community leader). We will contact them directly.</p>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="recommenderName" class="form-label">Recommender's Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control" name="recommender_name" id="recommenderName" required>
    </div>
    <div class="col-md-6">
      <label for="recommenderEmail" class="form-label">Recommender's Email <span class="text-danger">*</span></label>
      <input type="email" class="form-control" name="recommender_email" id="recommenderEmail" required>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="relationship" class="form-label">Relationship to You <span class="text-danger">*</span></label>
      <input type="text" class="form-control" name="recommender_relationship" id="relationship" required>
    </div>
  </div>

  <!-- Section: Additional Information -->
  <h5>Additional Information</h5>
  <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="financialNeed" class="form-label">Financial Need</label>
      <textarea class="form-control" name="financial_need" id="financialNeed" rows="2"></textarea>
    </div>
  </div>

  <div class="row g-3 mb-5">
    <div class="col-12">
      <label for="additionalInfo" class="form-label">Anything Else We Should Know?</label>
      <textarea class="form-control" name="additional_information" id="additionalInfo" rows="2"></textarea>
    </div>
  </div>

  <hr>

  <div class="mt-5 mx-auto">
    <button type="submit" class="btn btn-lg mt-4" style="background-color: rgb(7,5,55); color:white; font-size: 18px !important;"><i class="bi bi-file-earmark-text me-2"></i>&nbsp;Submit Application</button>
    <p class="mt-4 text-muted" style="font-size: 12px;">By submitting this application, you confirm that all information provided is accurate and complete.</p>
  </div>

</form>

<div id="submit-overlay" class="d-none" role="status" aria-live="polite">
  <div class="panel">
    <img class="crest" src="assets/images/logo.png" alt="Morgan Legacy Scholarship">

    <div class="spinner"></div>

    <h1>Submitting your application&hellip;</h1>
    <p class="sub">Please don&rsquo;t close this page &mdash; this will only take a moment.</p>

    <div class="steps" id="submit-steps">
      <div class="step">
        <div class="step-marker"></div>
        <span>Saving your responses</span>
      </div>
      <div class="step">
        <div class="step-marker"></div>
        <span>Notifying your recommender</span>
      </div>
      <div class="step">
        <div class="step-marker"></div>
        <span>Finishing up</span>
      </div>
    </div>

    <p class="footnote">Next: you'll land on a confirmation page letting you know your application was received.</p>
  </div>
</div>

<script>
  (function () {
    const form = document.getElementById('applicationForm');
    const overlay = document.getElementById('submit-overlay');
    const steps = document.querySelectorAll('#submit-steps .step');
    const submitButton = form.querySelector('button[type="submit"]');

    const STEP_INTERVAL_MS = 1100;
    // Time for the last checkmark to land (steps.length intervals), plus a
    // beat afterward so the fully-green state is actually seen before
    // moving on — not just however long the real request happens to take.
    const MIN_DISPLAY_MS = steps.length * STEP_INTERVAL_MS + 600;

    function runStepAnimation() {
      let i = 0;
      function advance() {
        if (i > 0) {
          steps[i - 1].classList.remove('active');
          steps[i - 1].classList.add('done');
        }
        if (i < steps.length) {
          steps[i].classList.add('active');
          i++;
          setTimeout(advance, STEP_INTERVAL_MS);
        }
      }
      advance();
    }

    function showError(message) {
      overlay.classList.add('d-none');
      submitButton.disabled = false;

      let alertBox = document.getElementById('submit-error-alert');
      if (!alertBox) {
        alertBox = document.createElement('div');
        alertBox.id = 'submit-error-alert';
        alertBox.className = 'alert alert-danger mt-3';
        form.insertBefore(alertBox, form.firstChild);
      }
      alertBox.textContent = message;
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    form.addEventListener('submit', function (event) {
      // Native "required" validation has already passed by the time this
      // fires. Take over the actual submission via fetch so we control
      // when navigation happens — a plain form POST would let the browser
      // navigate away the instant the server responds, which could cut
      // the step animation short if the server is fast.
      event.preventDefault();

      overlay.classList.remove('d-none');
      submitButton.disabled = true; // guard against double submission
      runStepAnimation();

      const minDisplay = new Promise((resolve) => setTimeout(resolve, MIN_DISPLAY_MS));
      const submission = fetch(form.action, {
        method: 'POST',
        body: new FormData(form)
      });

      Promise.all([submission, minDisplay])
        .then(([response]) => {
          if (response.redirected) {
            // Success: the server issued its normal redirect to
            // thank_you.php. Follow it now that the animation has had
            // time to finish.
            window.location.href = response.url;
            return;
          }
          // No redirect means the server hit its error path and echoed a
          // plain message instead. Show it inline — the form's data is
          // still intact since we never navigated away, so nothing the
          // applicant typed gets lost.
          return response.text().then(function (text) {
            showError(text || 'Something went wrong submitting your application. Please try again.');
          });
        })
        .catch(function () {
          showError('Something went wrong submitting your application. Please check your connection and try again.');
        });
    });
  })();
</script>

<script>
  // Word count for essay
  const essay = document.getElementById('essay');
  const wordCount = document.getElementById('wordCount');

  essay.addEventListener('input', () => {
    const words = essay.value.trim().split(/\s+/).filter(Boolean).length;
    wordCount.textContent = words;
  });
</script>

    
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