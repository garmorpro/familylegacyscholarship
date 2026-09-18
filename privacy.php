<?php
require_once 'app/db.php';
require_once 'path.php';

$lastUpdated = 'September 18, 2026';
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
    <title>Privacy Policy - Morgan Legacy Scholarship</title>
</head>
<body class="d-flex flex-column min-vh-100">


<?php include_once ROOT_PATH . '/assets/includes/header.php'; ?>


<main class="flex-fill">
<div class="container py-3" style="background-color: rgb(249,250,251);">
    <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; padding: 0 !important; border-color: rgb(241,242,243) !important;">
        <div class="case-accent"></div>

        <div class="card-body">

            <h2 class="text-center">
                Privacy Policy
            </h2>
            <p class="text-center text-muted" style="font-size: 14px;">Last updated: <?= htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8') ?></p>

            <p>
                This Privacy Policy describes the information the Morgan Family Legacy Scholarship ("we," "us," or
                "our") collects through this website, and how we use it.
            </p>

            <h4 class="pt-3">
                Summary
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <ul>
                <li class="mb-2">We only ask for the information we need to review your scholarship application.</li>
                <li class="mb-2">We use reasonable security measures to protect your information.</li>
                <li class="mb-2">We do not sell, trade, or share your personal information with third parties.</li>
                <li class="mb-2">We do not use advertising trackers or analytics cookies.</li>
                <li class="mb-2">If you're selected as a recipient, we'll ask before publishing your name or photo publicly.</li>
            </ul>

            <h4 class="pt-3">
                Your Consent
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <p>
                By submitting an application or otherwise providing information through this website, you agree to
                the terms of this Privacy Policy and consent to the collection and use of your information as
                described here. If you do not agree with these practices, please do not submit an application or
                otherwise provide information through this website.
            </p>

            <h4 class="pt-3">
                Information We Collect
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">

            <h5>Scholarship Applications</h5>
            <p>When you apply, we collect the information you submit on the application form, including:</p>
            <ul>
                <li class="mb-2">Contact details: name, email address, and phone number</li>
                <li class="mb-2">Academic information: GPA, expected graduation year, intended school, and intended major</li>
                <li class="mb-2">Your essay, extracurricular activities, leadership experience, and community service</li>
                <li class="mb-2">Optional information you choose to share, such as financial need or additional context</li>
                <li class="mb-2">Your recommender's name, email address, and relationship to you, so we can request a letter of recommendation on your behalf</li>
            </ul>

            <h5>Saved / In-Progress Applications</h5>
            <p>
                If you use "Save &amp; Finish Later," we store your in-progress responses along with your email
                address so we can send you a private link to resume where you left off. Saved, unsubmitted
                applications are not visible to reviewers and are automatically deleted once the application
                window for that cycle closes.
            </p>

            <h5>Letters of Recommendation</h5>
            <p>
                Recommenders receive a private, one-time link to submit a letter on your behalf. We collect the
                content they submit along with the date it was received.
            </p>

            <h5>Automatically Collected Information</h5>
            <p>
                Like most websites, we automatically log standard technical information such as IP address and
                request timing for security purposes — for example, to detect and block spam or automated form
                submissions. We do not use advertising trackers or analytics cookies, and we do not sell or share
                your information with data brokers or advertisers.
            </p>

            <h4 class="pt-3">
                How We Use Your Information
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <ul>
                <li class="mb-2">To evaluate your scholarship application and communicate with you about its status</li>
                <li class="mb-2">To request and collect letters of recommendation on your behalf</li>
                <li class="mb-2">To send you application-related emails, such as save-and-resume links or a decision notice</li>
                <li class="mb-2">To administer the selection process, including committee review and voting</li>
                <li class="mb-2">To protect the application process from spam and fraudulent submissions</li>
            </ul>
            <p>
                We do not use your information for advertising, and we do not sell, rent, or trade your personal
                information to third parties.
            </p>

            <h5>If You Are Selected as a Recipient</h5>
            <p>
                If you are chosen as a scholarship recipient, your first name, last name, school, and intended major
                may be published on the public <a href="recipients.php">Recipients</a> page of this website to
                celebrate your achievement. We will only publish a photo of you if you provide one and agree to its
                use. If you'd prefer not to be featured publicly, contact us at the email below and we'll accommodate
                your request.
            </p>

            <h4 class="pt-3">
                Data Retention
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <p>
                We keep completed applications for a limited number of past scholarship cycles for record-keeping
                purposes, after which older cycles are permanently deleted. Saved but never-submitted applications
                are deleted automatically once that cycle's application window closes. You may request earlier
                deletion of your information at any time using the contact information below.
            </p>

            <h4 class="pt-3">
                Your Choices Regarding Your Information
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <ul>
                <li class="mb-2">You can ask us what information we have about you</li>
                <li class="mb-2">You can ask us to correct inaccurate information</li>
                <li class="mb-2">You can ask us to delete your information, including a saved but unsubmitted application</li>
                <li class="mb-2">You can ask us not to publish your name or photo if you're selected as a recipient</li>
            </ul>
            <p>
                To exercise any of these choices, contact us using the information below.
            </p>

            <h4 class="pt-3">
                Third-Party Links
            </h4>
            <hr style="color: rgb(36,45,87) !important; border: 2px solid rgb(36,45,87) !important; opacity: 1;">
            <p class="mb-5">
                This website may contain links to other websites. If you click one of those links, you'll leave our
                site and be subject to that site's own privacy practices. We aren't responsible for the content or
                privacy practices of any third-party site.
            </p>

            <hr>

            <p class="text-muted text-center" style="font-size: 14px;">
                Questions about this policy or your information? Contact us at
                <a href="mailto:scholarship@themorganlegacy.com" class="footer-link">scholarship@themorganlegacy.com</a>.
                We may update this policy from time to time; the "Last updated" date above reflects the most recent
                revision.
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
