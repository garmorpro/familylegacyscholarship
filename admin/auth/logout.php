<?php
session_start();

require_once '../../app/db.php';
require_once '../../path.php';

// If admin is logged in, update database
if (isset($_SESSION['admin_id'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE admin_users
            SET logged_in = FALSE
            WHERE id = :id
        ");
        $stmt->execute(['id' => $_SESSION['admin_id']]);
    } catch (PDOException $e) {
        // Log error but still continue with logout
        error_log("Logout DB error: " . $e->getMessage());
    }
}

// Destroy all session data
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

// Redirect to login page
header('Location: ' . BASE_URL . '/');
exit;
