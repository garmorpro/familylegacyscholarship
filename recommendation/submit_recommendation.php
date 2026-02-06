<?php
require '../app/db.php';

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
        $update = $pdo->prepare("
            UPDATE recommendations
            SET recommendation = :content, status = 'completed', completed_date = NOW()
            WHERE id = :id
        ");
        $update->execute([
            ':content' => $content,
            ':id' => $rec['id']
        ]);

        $success = "Thank you! Your recommendation has been submitted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>The Morgan Legacy Scholarship - Submit Recommendation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.tiny.cloud/1/7kainuaawjddfzf3pj7t2fm3qdjgq5smjfjtsw3l4kqfd1h4/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<style>
  body {
    background-color: #f8f9fa;
  }
  .scholarship-header {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }
  .scholarship-header img {
    height: 80px;
  }
  .scholarship-header h1 {
    font-size: 1.8rem;
    margin: 0;
    color: #d63384; /* pink/purple accent */
  }
  .form-container {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }
</style>
</head>
<body class="p-4">

<div class="container">

    <!-- Scholarship Header -->
    <div class="scholarship-header">
        <img src="/assets/logo.png" alt="Morgan Legacy Scholarship Logo">
        <h1>The Morgan Legacy Scholarship</h1>
    </div>

    <!-- Applicant Info -->
    <div class="form-container mb-4">
        <p><strong>Applicant:</strong> <?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></p>
        <p><strong>Intended School:</strong> <?= htmlspecialchars($rec['intended_school']) ?></p>
        <p><strong>Intended Major:</strong> <?= htmlspecialchars($rec['intended_major']) ?></p>
        <p><strong>Recommender:</strong> <?= htmlspecialchars($rec['recommender_name']) ?> (<?= htmlspecialchars($rec['recommender_email']) ?>)</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Recommendation Form -->
    <?php if ($rec['status'] !== 'completed'): ?>
    <div class="form-container">
        <form method="POST">
            <div class="mb-3">
                <label for="content" class="form-label">Your Recommendation</label>
                <textarea name="content" id="content" rows="10" class="form-control"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Recommendation</button>
        </form>
    </div>
    <?php else: ?>
        <div class="alert alert-info">You have already submitted this recommendation.</div>
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
