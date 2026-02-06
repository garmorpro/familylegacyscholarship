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
            SET content = :content, status = 'completed', completed_date = NOW()
            WHERE id = :id
        ");
        $update->execute([
            ':content' => $content,
            ':id' => $rec['id']
        ]);

        $success = "Thank you! Your recommendation has been submitted.";
        // Optionally disable form after submit
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Recommendation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4">Recommendation for <?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></h1>
    <p><strong>Intended School:</strong> <?= htmlspecialchars($rec['intended_school']) ?></p>
    <p><strong>Intended Major:</strong> <?= htmlspecialchars($rec['intended_major']) ?></p>
    <p><strong>Recommender:</strong> <?= htmlspecialchars($rec['recommender_name']) ?> (<?= htmlspecialchars($rec['recommender_email']) ?>)</p>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($rec['status'] !== 'completed'): ?>
    <form method="POST">
        <div class="mb-3">
            <label for="content" class="form-label">Your Recommendation</label>
            <textarea name="content" id="content" rows="10" class="form-control"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Recommendation</button>
    </form>
    <?php else: ?>
        <div class="alert alert-info">You have already submitted this recommendation.</div>
    <?php endif; ?>
</div>


<!-- Initialize TinyMCE -->
  <script>
    tinymce.init({
      selector: '#content', // CSS selector for the textarea
      height: 300,             // editor height
      menubar: false,          // hide menu if you want
      plugins: [
        'advlist autolink lists link image charmap print preview anchor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table code help wordcount'
      ],
      toolbar: 'undo redo | formatselect | ' +
               'bold italic backcolor | alignleft aligncenter ' +
               'alignright alignjustify | bullist numlist outdent indent | ' +
               'removeformat | help'
    });
  </script>

</body>
</html>
