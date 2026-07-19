<?php
// Reusable CSRF token helpers. Assumes a session is already active
// (session_start() must have been called before these are used).

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Verifies the token for the current request and stops execution with a
// 403 if it's missing or doesn't match. Checks, in order: POST field,
// JSON body field, X-CSRF-Token header.
function csrf_require(): void {
    $token = $_POST['csrf_token'] ?? null;

    if ($token === null && !empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
        $json = json_decode(file_get_contents('php://input'), true);
        $token = $json['csrf_token'] ?? null;
    }

    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (!csrf_verify($token)) {
        http_response_code(403);
        die('Security check failed (invalid or expired token). Please go back, refresh the page, and try again.');
    }
}
