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



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/db.php';

// Determine if running for a single recommendation (GET) or for cron (all not_sent)
$recommendations = [];

if (isset($_GET['id'])) {
    // Single recommendation via browser
    $recId = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT r.id, r.recommender_name, r.recommender_email, r.token, 
               s.first_name, s.last_name
        FROM recommendations r
        JOIN scholarship_applications s ON s.id = r.scholarship_application_id
        WHERE r.id = :id
    ");
    $stmt->execute([':id' => $recId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) die("Recommendation not found.");

    $recommendations[] = $rec;
} else {
    // Cron mode: send all recommendations with status 'not_sent'
    $stmt = $pdo->prepare("
        SELECT r.id, r.recommender_name, r.recommender_email, r.token, 
               s.first_name, s.last_name
        FROM recommendations r
        JOIN scholarship_applications s ON s.id = r.scholarship_application_id
        WHERE r.status = 'not_sent'
    ");
    $stmt->execute();
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If no recommendations to send, exit
if (!$recommendations) {
    echo "No recommendations to send.\n";
    exit;
}

// Loop through each recommendation
foreach ($recommendations as $rec) {
    $applicantName = $rec['first_name'] . ' ' . $rec['last_name'];
    $link = "https://themorganlegacy.com/recommendation/submit_recommendation.php?token={$rec['token']}";

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
        $mail->setFrom('garrett@morganserver.com', 'The Morgan Legacy Scholarship');
        $mail->addAddress($rec['recommender_email'], $rec['recommender_name']);

        // Attach logo
        $mail->AddEmbeddedImage(__DIR__ . '/../assets/images/logo.png', 'logoimg');

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Recommendation Request for {$applicantName}";
        $mail->Body = "
            <p><img src='cid:logoimg' alt='Morgan Legacy Scholarship Logo' style='height:80px;'></p>
            <p>Dear {$rec['recommender_name']},</p>
            <p>{$applicantName} has applied for The Morgan Legacy Scholarship and listed you as a recommender.</p>
            <p>Please submit your recommendation using the link below:</p>
            <p><a href='{$link}' target='_blank'>Submit Recommendation</a></p>
            <p>Thank you for your support!</p>
            <p>The Morgan Legacy Scholarship Committee</p>
        ";

        $mail->send();
        echo "Email sent to {$rec['recommender_email']}\n";

        // Update recommendation status
        $update = $pdo->prepare("
            UPDATE recommendations
            SET status = 'sent', requested_date = NOW()
            WHERE id = :id
        ");
        $update->execute([':id' => $rec['id']]);

    } catch (Exception $e) {
        echo "Failed to send to {$rec['recommender_email']}: {$mail->ErrorInfo}\n";
    }
}

// If running via browser, redirect back
if (isset($_GET['id'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
