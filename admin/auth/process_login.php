<?php
session_start();
require_once '../../app/db.php';
require_once '../../path.php'; 

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/auth/');
    exit;
}

// Basic input sanitation
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: login.php?error=missing');
    exit;
}

try {
    // Get admin user
    $stmt = $pdo->prepare("
        SELECT id, email, password_hash, is_active, failed_login_attempts
        FROM admin_users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generic failure (do not reveal which part failed)
    if (!$admin || !$admin['is_active']) {
        header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
        // header('Location: login.php?error=invalid');
        exit;
    }

    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {

        // Increment failed attempts
        $stmt = $pdo->prepare("
            UPDATE admin_users
            SET failed_login_attempts = failed_login_attempts + 1,
                last_failed_login = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $admin['id']]);

        header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
        // header('Location: login.php?error=invalid');
        exit;
    }

    // ✅ SUCCESS — reset failed attempts
    $stmt = $pdo->prepare("
        UPDATE admin_users
        SET failed_login_attempts = 0,
            last_login_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute(['id' => $admin['id']]);

    // Harden session
    session_regenerate_id(true);

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['is_admin'] = true;

    // header('Location: /admin/');
    header('Location: ' . BASE_URL . '/admin/');
    exit;

} catch (PDOException $e) {
    // Log this server-side in real life
    error_log($e->getMessage());
    // header('Location: login.php?error=server');
    header('Location: ' . BASE_URL . '/admin/auth/?error=server');
    exit;
}
