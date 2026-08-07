<?php
require '../app/db.php'; // your PDO connection
require_once '../app/require_admin.php';
require_once '../app/csrf.php';
require_once '../app/recommendation_mailer.php';

if (!isset($_GET['id'])) {
    die("Recommendation ID missing.");
}

if (!csrf_verify($_GET['csrf_token'] ?? null)) {
    http_response_code(403);
    die('Security check failed (invalid or expired token). Please go back, refresh the page, and try again.');
}

$recId = (int)$_GET['id'];

if (!send_recommendation_request_email($pdo, $config, $recId)) {
    die("Email could not be sent. Check the server error log for details.");
}

// Some browsers/privacy extensions strip the Referer header entirely --
// fall back to the dashboard instead of redirecting to an empty
// location (which would 404 or just do nothing).
$redirectTo = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: " . $redirectTo);
exit;
