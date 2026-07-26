<?php

// Only allow localhost (server) access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden</title>
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: #fef2f2;
                color: #b91c1c;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
                text-align: center;
            }
            .container {
                max-width: 400px;
            }
            h1 {
                font-size: 3rem;
                margin-bottom: 1rem;
            }
            p {
                font-size: 1.2rem;
            }
            a {
                color: #b91c1c;
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>403 Forbidden</h1>
            <p>Access Denied. You are not allowed to view this page.</p>
            <p>If you believe this is an error, contact the server administrator.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}



require __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/recommendation_mailer.php';

// Determine if running for a single recommendation (GET) or for cron (all not_sent)
$recommendationIds = [];

if (isset($_GET['id'])) {
    // Single recommendation via browser
    $recId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT id FROM recommendations WHERE id = :id");
    $stmt->execute([':id' => $recId]);
    if (!$stmt->fetchColumn()) die("Recommendation not found.");

    $recommendationIds[] = $recId;
} else {
    // Cron mode: send all recommendations with status 'not_sent'
    $stmt = $pdo->query("SELECT id FROM recommendations WHERE status = 'not_sent'");
    $recommendationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// If no recommendations to send, exit
if (!$recommendationIds) {
    echo "No recommendations to send.\n";
    exit;
}

// Send each recommendation
foreach ($recommendationIds as $recId) {
    if (send_recommendation_request_email($pdo, $config, $recId)) {
        echo "Email sent for recommendation {$recId}\n";
    } else {
        echo "Failed to send for recommendation {$recId} (see error log)\n";
    }
}

// If running via browser, redirect back
if (isset($_GET['id'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
