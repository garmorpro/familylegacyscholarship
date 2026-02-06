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
    header('Location: ' . BASE_URL . '/admin/auth/?error=missing');
    exit;
}

try {
    // Fetch admin user
    $stmt = $pdo->prepare("
        SELECT
            id,
            email,
            password_hash,
            is_active,
            failed_login_attempts
        FROM admin_users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generic failure (no info leaks)
    if (!$admin) {
        header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
        exit;
    }

    // Account locked / inactive
    if (!$admin['is_active']) {
        header('Location: ' . BASE_URL . '/admin/auth/?error=locked');
        exit;
    }

    // Hard stop if already locked by attempts
    if ($admin['failed_login_attempts'] >= 3) {
        header('Location: ' . BASE_URL . '/admin/auth/?error=locked');
        exit;
    }

    // Password check
    if (!password_verify($password, $admin['password_hash'])) {

        $newAttempts = $admin['failed_login_attempts'] + 1;

        // Lock account on 3rd failure
        if ($newAttempts >= 3) {
            $stmt = $pdo->prepare("
                UPDATE admin_users
                SET
                    failed_login_attempts = :attempts,
                    is_active = false,
                    last_failed_login = NOW()
                WHERE id = :id
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE admin_users
                SET
                    failed_login_attempts = :attempts,
                    last_failed_login = NOW()
                WHERE id = :id
            ");
        }

        $stmt->execute([
            'attempts' => $newAttempts,
            'id' => $admin['id']
        ]);

        header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
        exit;
    }

    // ✅ SUCCESS — reset counters
    $stmt = $pdo->prepare("
        UPDATE admin_users
        SET
            failed_login_attempts = 0,
            last_login_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute(['id' => $admin['id']]);

    // Harden session
    session_regenerate_id(true);

    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['is_admin']    = true;

    header('Location: ' . BASE_URL . '/admin/');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: ' . BASE_URL . '/admin/auth/?error=server');
    exit;
}
