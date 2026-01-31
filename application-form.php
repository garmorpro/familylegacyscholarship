<?php
require_once 'app/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = insert_application($_POST);
        $message = "Application submitted successfully! Your application ID: $id";
    } catch (Exception $e) {
        $message = "Error submitting application: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Morgan Legacy Scholarship Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<main class="flex-fill">
<div class="container py-3">

<div class="card shadow-sm p-4">

<h4>Scholarship Application</h4>

<?php if($message): ?>
<div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" action="">

  <!-- Personal Info -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="firstName" class="form-label">First Name</label>
      <input type="text" class="form-control" id="firstName" name="first_name" required>
    </div>
    <div class="col-md-6">
      <label for="lastName" class="form-label">Last Name</label>
      <input type="text" class="form-control" id="lastName" name="last_name" required>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="col-md-6">
      <label for="phone" class="form-label">Phone Number</label>
      <input type="tel" class="form-control" id="phone" name="phone">
    </div>
  </div>

  <!-- School Info -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="gradYear" class="form-label">Expected Graduation Year</label>
      <input type="number" class="form-control" id="gradYear" name="expected_graduation_year" value="<?= date('Y')+3 ?>" required>
    </div>
    <div class="col-md-6">
      <label for="gpa" class="form-label">Current Weighted GPA</label>
      <input type="text" class="form-control" id="gpa" name="gpa" placeholder="4.0" required>
    </div>
  </div>

  <!-- Post-Secondary Plans -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="institutionType" class="form-label">Type of Institution</label>
      <select class="form-select" id="institutionType" name="institution_type">
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
      <label for="intendedSchool" class="form-label">Intended School/Institution Name</label>
      <input type="text" class="form-control" id="intendedSchool" name="intended_school" placeholder="School Name">
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="intendedMajor" class="form-label">Intended Major/Program of Study</label>
      <input type="text" class="form-control" id="intendedMajor" name="intended_major" placeholder="Major/Program">
    </div>
  </div>

  <!-- Activities & Essay -->
  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="extracurricular" class="form-label">Extracurricular Activities</label>
      <textarea class="form-control" id="extracurricular" name="extracurricular" rows="2"></textarea>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="leadership" class="form-label">Leadership Roles</label>
      <textarea class="form-control" id="leadership" name="leadership" rows="2"></textarea>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="communityService" class="form-label">Community Service & Volunteer Work</label>
      <textarea class="form-control" id="communityService" name="community_service" rows="2"></textarea>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="essay" class="form-label">Essay Question</label>
      <textarea class="form-control" id="essay" name="essay" rows="6"></textarea>
      <div class="text-end mt-1" style="font-size: 12px;">Word count: <span id="wordCount">0</span> words</div>
    </div>
  </div>

  <!-- Recommendation -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="recommenderName" class="form-label">Recommender's Name</label>
      <input type="text" class="form-control" id="recommenderName" name="recommender_name">
    </div>
    <div class="col-md-6">
      <label for="recommenderEmail" class="form-label">Recommender's Email</label>
      <input type="email" class="form-control" id="recommenderEmail" name="recommender_email">
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="relationship" class="form-label">Relationship to You</label>
      <input type="text" class="form-control" id="relationship" name="relationship">
    </div>
  </div>

  <!-- Additional Info -->
  <div class="row g-3 mb-3">
    <div class="col-12">
      <label for="financialNeed" class="form-label">Financial Need</label>
      <textarea class="form-control" id="financialNeed" name="financialNeed" rows="2"></textarea>
    </div>
  </div>

  <div class="row g-3 mb-5">
    <div class="col-12">
      <label for="additionalInfo" class="form-label">Anything Else?</label>
      <textarea class="form-control" id="additionalInfo" name="additionalInfo" rows="2"></textarea>
    </div>
  </div>

  <div class="mt-4">
    <button type="submit" class="btn btn-lg btn-primary">Submit Application</button>
  </div>

</form>

<script>
  // Word count
  const essay = document.getElementById('essay');
  const wordCount = document.getElementById('wordCount');
  essay.addEventListener('input', () => {
    const words = essay.value.trim().split(/\s+/).filter(Boolean).length;
    wordCount.textContent = words;
  });
</script>

</div>
</main>
</body>
</html>
