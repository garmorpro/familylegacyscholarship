<?php
// Lightweight, self-contained spam/bot protection for public forms --
// no external service, API key, or visible challenge for real visitors.
// Three independent checks, meant to be combined by the caller:
//   1. Honeypot field (see is_honeypot_filled) -- bots that auto-fill
//      every field trip it; real visitors never see it.
//   2. Minimum time-on-page (see submission_too_fast) -- a server-side
//      session timestamp, not a value a bot can just copy forward.
//   3. Per-IP rate limit (see is_rate_limited / record_submission_attempt)
//      -- backed by the submission_attempts table.

// The real visitor IP -- this site sits behind Cloudflare, so
// $_SERVER['REMOTE_ADDR'] alone is just Cloudflare's own proxy IP for
// every single visitor, which would make the rate limit apply to
// everyone collectively instead of per-visitor.
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// True if the honeypot field was filled in -- it's positioned off-screen
// and never shown to real visitors, so only an automated script blindly
// filling every field in the form would ever put a value in it.
function is_honeypot_filled(string $fieldName = 'website'): bool {
    return !empty(trim($_POST[$fieldName] ?? ''));
}

// True if this submission arrived faster than a human could plausibly
// have actually filled out the form -- $sessionKey should be set to the
// current time when the form is first rendered (GET), then checked here
// on POST.
function submission_too_fast(string $sessionKey, int $minSeconds = 3): bool {
    $startedAt = $_SESSION[$sessionKey] ?? null;
    if (!$startedAt) {
        return false; // no timestamp to compare against -- don't block on this alone
    }
    return (time() - $startedAt) < $minSeconds;
}

// True if this IP has made too many attempts at this specific form
// recently. Call record_submission_attempt() once per attempt that gets
// this far (whether or not the underlying form data itself is ultimately
// valid), so someone repeatedly retrying a bad submission still runs
// into the wall.
function is_rate_limited(PDO $pdo, string $formType, int $maxAttempts = 5, int $windowMinutes = 15): bool {
    $ip = get_client_ip();
    $cutoff = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM submission_attempts
        WHERE ip_address = :ip AND form_type = :form_type AND created_at > :cutoff
    ");
    $stmt->execute([':ip' => $ip, ':form_type' => $formType, ':cutoff' => $cutoff]);
    return (int) $stmt->fetchColumn() >= $maxAttempts;
}

function record_submission_attempt(PDO $pdo, string $formType): void {
    $ip = get_client_ip();
    $stmt = $pdo->prepare("INSERT INTO submission_attempts (ip_address, form_type) VALUES (:ip, :form_type)");
    $stmt->execute([':ip' => $ip, ':form_type' => $formType]);

    // Opportunistic cleanup instead of a dedicated cron job -- this table
    // only needs to hold a rolling window, so on roughly 1 in 20 attempts
    // just sweep anything older than a day.
    if (random_int(1, 20) === 1) {
        $pdo->exec("DELETE FROM submission_attempts WHERE created_at < NOW() - INTERVAL '1 day'");
    }
}
