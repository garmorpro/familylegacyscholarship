<?php
session_start();

require_once '../../app/db.php';
require_once '../../app/csrf.php';
require_once '../../path.php';
require_once '../../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/auth/');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
    exit;
}

// Input sanitation
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ' . BASE_URL . '/admin/auth/?error=missing');
    exit;
}

try {
    // Fetch admin user
    $stmt = $pdo->prepare("
        SELECT id, email, password_hash, is_active, failed_login_attempts
        FROM admin_users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generic failure
    if (!$admin) {
        header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
        exit;
    }

    // Already locked
    if (!$admin['is_active'] || $admin['failed_login_attempts'] >= 3) {
        header('Location: ' . BASE_URL . '/admin/auth/?error=locked');
        exit;
    }

    // Password check
    if (!password_verify($password, $admin['password_hash'])) {

        $newAttempts = $admin['failed_login_attempts'] + 1;

        if ($newAttempts >= 3) {
            // Generate unlock token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Lock account + store token
            $stmt = $pdo->prepare("
                UPDATE admin_users
                SET failed_login_attempts = :attempts,
                    is_active = false,
                    unlock_token = :token,
                    unlock_token_expires = :expires,
                    last_failed_login = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'attempts' => $newAttempts,
                'token' => $token,
                'expires' => $expires,
                'id' => $admin['id']
            ]);

            // Send email with unlock link
            $unlockLink = BASE_URL . "/admin/auth/unlock.php?token=$token";

            $mail = new PHPMailer(true);
            try {
                // SMTP settings
                $mail->isSMTP();
                $mail->Host       = $config['smtp']['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp']['username'];
                $mail->Password   = $config['smtp']['password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp']['port'];

                // Recipients
                $mail->setFrom('scholarship@themorganlegacy.com', 'The Morgan Legacy');
                $mail->addAddress($admin['email']);

                // Content
                $mail->AddEmbeddedImage('../../assets/images/logo.png', 'logoimg');
                $mail->isHTML(true);
                $mail->Subject = "Account Lockout: TheMorganLegacy Admin Account";
                $mail->Body = "
                    <p><img src='cid:logoimg' alt='Morgan Legacy Scholarship Logo' style='height:80px;'></p>
                    Your admin account has been locked after 3 failed login attempts.<br>
                    Click the link below to unlock your account (valid 1 hour):<br>
                    <a href='$unlockLink'>$unlockLink</a>
                ";
                $mail->send();

            } catch (Exception $e) {
                error_log("Mail could not be sent: {$mail->ErrorInfo}");
            }

        } else {
            // Just increment failed attempts
            $stmt = $pdo->prepare("
                UPDATE admin_users
                SET failed_login_attempts = :attempts,
                    last_failed_login = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'attempts' => $newAttempts,
                'id' => $admin['id']
            ]);
        }

        header('Location: ' . BASE_URL . '/admin/auth/?error=invalid');
        exit;
    }

    // ✅ Success — reset counters and mark logged_in
    $stmt = $pdo->prepare("
        UPDATE admin_users
        SET failed_login_attempts = 0,
            last_login_at = NOW(),
            logged_in = TRUE
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
