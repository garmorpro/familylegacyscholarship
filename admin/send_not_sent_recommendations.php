<?php
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
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'garrett.morgan.pro@gmail.com';
        $mail->Password   = '***REMOVED-SMTP-PASSWORD***';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

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
