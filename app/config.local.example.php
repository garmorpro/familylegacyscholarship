<?php
// Copy this file to app/config.local.php and fill in real values.
// app/config.local.php is gitignored — it must be created by hand on every
// environment that runs this app (including the production server), and
// its values should never be committed to this repo.

return [
    'db' => [
        'host' => 'localhost',
        'port' => 5432,
        'name' => 'morgan_legacy_scholarship',
        'user' => 'dbadmin',
        'pass' => 'CHANGE_ME',
    ],
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'CHANGE_ME@gmail.com',
        'password' => 'CHANGE_ME_GMAIL_APP_PASSWORD',
    ],
];
