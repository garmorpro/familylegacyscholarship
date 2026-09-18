<?php
// Drop-in replacement for a bare session_start() call -- sets the session
// cookie flags PHP doesn't apply on its own before starting the session,
// then starts it. Require this instead of calling session_start() directly
// anywhere in the app.
//
// - httponly: JS on this site never needs to read the session cookie, so
//   there's no reason to let it -- keeps a session cookie out of reach of
//   any future XSS bug.
// - secure: the app is HTTPS-only in production (see BASE_URL in path.php),
//   so the cookie should never be sent over a plain HTTP connection.
// - samesite=Lax: blocks the cookie on cross-site POSTs (the main CSRF
//   vector) while still allowing it on a normal top-level link click, e.g.
//   an admin following a link from their email client.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
