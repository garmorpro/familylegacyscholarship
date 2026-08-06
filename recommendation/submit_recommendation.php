<?php
require '../app/db.php';
require_once '../app/functions.php';

// Get token from URL
$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid link.");
}

// Fetch recommendation + applicant info
$stmt = $pdo->prepare("
    SELECT r.id, r.recommender_name, r.recommender_email, r.status,
           s.first_name, s.last_name, s.intended_school, s.intended_major
    FROM recommendations r
    JOIN scholarship_applications s ON s.id = r.scholarship_application_id
    WHERE r.token = :token
    LIMIT 1
");
$stmt->execute([':token' => $token]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rec) {
    die("Invalid or expired recommendation link.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    if (trim($content) === '') {
        $error = "Please enter your recommendation.";
    } else {
        $content = sanitize_recommendation_html($content);
        $update = $pdo->prepare("
            UPDATE recommendations
            SET recommendation = :content, status = 'completed', completed_date = NOW()
            WHERE id = :id
        ");
        $update->execute([
            ':content' => $content,
            ':id' => $rec['id']
        ]);

        // Update status in local variable so page reflects completion immediately
        $rec['status'] = 'completed';
        $success = "Thank you! Your recommendation has been submitted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>The Morgan Legacy Scholarship - Submit Recommendation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<script src="https://cdn.tiny.cloud/1/7kainuaawjddfzf3pj7t2fm3qdjgq5smjfjtsw3l4kqfd1h4/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<style>
  body { background-color: rgb(249,250,251); }

  .page-header { background: #fff; border-bottom: 1px solid rgb(241,242,243); padding: 20px 0; margin-bottom: 32px; }
  .page-header-inner { display: flex; align-items: center; gap: 16px; max-width: 760px; margin: 0 auto; padding: 0 20px; }
  .page-header img { height: 56px; }
  .page-header h1 { font-size: 1.3rem; font-weight: 700; color: rgb(7,5,55); margin: 0; }
  .page-header .tagline { font-size: 0.85rem; color: #8a8a94; margin: 0; }

  .container-narrow { max-width: 760px; margin: 0 auto; padding: 0 20px 60px; }

  .case-card { background: #fff; border-radius: 16px; border: 1px solid rgb(241,242,243); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; }
  .case-accent { height: 5px; background: linear-gradient(90deg, rgb(7,5,55), #C5A059); }

  .info-grid { padding: 24px 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px 24px; }
  .info-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9a9aa5; margin-bottom: 3px; }
  .info-value { font-size: 14.5px; font-weight: 600; color: #212529; }

  .highlight-banner { margin: 24px 28px 0; background: rgba(197,160,89,0.12); border: 1px solid rgba(197,160,89,0.35); border-radius: 10px; padding: 14px 18px; font-size: 14.5px; color: #6b5620; display: flex; align-items: center; gap: 10px; }
  .highlight-banner i { color: #C5A059; font-size: 18px; }

  .form-section { padding: 24px 28px 28px; }
  .form-label-lg { font-size: 15px; font-weight: 700; color: #212529; margin-bottom: 4px; }
  .form-sub { font-size: 12.5px; color: #8a8a94; margin-bottom: 14px; }

  .submit-btn { background: rgb(7,5,55); color: #fff; font-weight: 600; padding: 11px 28px; border-radius: 8px; border: none; font-size: 15px; margin-top: 18px; }
  .submit-btn:hover { background: rgb(12,9,70); color: #fff; }

  .completed-card { padding: 40px 28px; text-align: center; }
  .completed-icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(25,135,84,0.12); color: #198754; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px; }
  .completed-title { font-size: 17px; font-weight: 700; color: #212529; margin-bottom: 6px; }
  .completed-sub { font-size: 13.5px; color: #8a8a94; }
</style>
</head>
<body>

<div class="page-header">
  <div class="page-header-inner">
    <img src="../assets/images/logo.png" alt="Morgan Legacy Scholarship Logo">
    <div>
      <h1>The Morgan Legacy Scholarship</h1>
      <p class="tagline">Recommendation Letter</p>
    </div>
  </div>
</div>

<div class="container-narrow">

    <!-- Applicant / recommender info -->
    <div class="case-card">
        <div class="case-accent"></div>
        <div class="info-grid">
            <div>
                <div class="info-label">Applicant</div>
                <div class="info-value"><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></div>
            </div>
            <div>
                <div class="info-label">Recommender</div>
                <div class="info-value"><?= htmlspecialchars($rec['recommender_name']) ?></div>
                <div style="font-size: 12.5px; color: #8a8a94;"><?= htmlspecialchars($rec['recommender_email']) ?></div>
            </div>
            <div>
                <div class="info-label">Intended School</div>
                <div class="info-value"><?= htmlspecialchars($rec['intended_school']) ?></div>
            </div>
            <div>
                <div class="info-label">Intended Major</div>
                <div class="info-value"><?= htmlspecialchars($rec['intended_major']) ?></div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Recommendation Form -->
    <?php if ($rec['status'] !== 'completed'): ?>
    <div class="case-card">
        <div class="case-accent"></div>

        <div class="highlight-banner">
            <i class="bi bi-info-circle-fill"></i>
            <div>This recommendation is for <strong><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></strong>.</div>
        </div>

        <form method="POST" class="form-section">
            <div class="mb-3">
                <label for="content" class="form-label-lg">Your Recommendation</label>
                <div class="form-sub">Share your honest assessment of the applicant -- their character, achievements, and why they'd make a strong recipient.</div>
                <textarea name="content" id="content" rows="10" class="form-control"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="submit-btn">Submit Recommendation</button>
        </form>
    </div>
    <?php else: ?>
        <!-- Show info only when completed -->
        <div class="case-card">
            <div class="case-accent"></div>
            <div class="completed-card">
                <div class="completed-icon"><i class="bi bi-check-lg"></i></div>
                <div class="completed-title">Recommendation submitted</div>
                <div class="completed-sub">Thank you -- your recommendation for <strong><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></strong> has already been received.</div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Initialize TinyMCE -->
<script>
tinymce.init({
  selector: '#content',
  height: 300,
  menubar: false,
  plugins: [
    'advlist autolink lists link charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table code help wordcount'
  ],
  toolbar: 'undo redo | formatselect | ' +
           'bold italic underline | alignleft aligncenter ' +
           'alignright alignjustify | bullist numlist outdent indent | ' +
           'removeformat | help',
  branding: false
});
</script>

</body>
</html>
