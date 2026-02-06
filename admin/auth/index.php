<?php
session_start();



$errorMessage = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':
            $errorMessage = 'Invalid email or password.';
            break;
        case 'missing':
            $errorMessage = 'Please enter both email and password.';
            break;
        case 'server':
            $errorMessage = 'Something went wrong. Please try again.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #f6f7fb;
  --card: #ffffff;
  --primary: #2563eb;
  --primary-hover: #1e40af;
  --text: #0f172a;
  --muted: #64748b;
  --border: #e5e7eb;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  min-height: 100vh;
  font-family: 'Inter', system-ui, sans-serif;
  background: var(--bg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text);
}

.login-card {
  width: 100%;
  max-width: 380px;
  background: var(--card);
  padding: 2.25rem;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,.08);
}

.login-card h1 {
  margin: 0 0 .5rem;
  font-size: 1.6rem;
  font-weight: 600;
}

.login-card p {
  margin: 0 0 1.75rem;
  color: var(--muted);
  font-size: .95rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

label {
  display: block;
  margin-bottom: .35rem;
  font-size: .85rem;
  font-weight: 500;
}

input {
  width: 100%;
  padding: .65rem .75rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  font-size: .95rem;
}

input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(37,99,235,.15);
}

button {
  width: 100%;
  padding: .7rem;
  border-radius: 10px;
  border: none;
  font-size: .95rem;
  font-weight: 500;
  background: var(--primary);
  color: #fff;
  cursor: pointer;
  transition: background .15s ease;
}

button:hover {
  background: var(--primary-hover);
}

.footer-text {
  margin-top: 1.5rem;
  text-align: center;
  font-size: .75rem;
  color: var(--muted);
}

.error-message {
  margin-bottom: 1rem;
  padding: .6rem .75rem;
  border-radius: 8px;
  font-size: .85rem;
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

</style>
</head>

<body>

<div class="login-card">
  <h1>Admin Access</h1>
  <p>Sign in to manage the admin portal</p>

  <?php if ($errorMessage): ?>
  <div class="error-message">
    <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>


  <form method="POST" action="process_login.php">
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>

    <button type="submit">Sign In</button>
  </form>

  <div class="footer-text">
    © <?= date('Y') ?> The Morgan Legacy
  </div>
</div>

</body>
</html>
