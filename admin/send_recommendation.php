<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Composer autoload
require '../app/db.php'; // your PDO connection
require_once '../app/require_admin.php';
require_once '../app/csrf.php';

if (!isset($_GET['id'])) {
    die("Recommendation ID missing.");
}

if (!csrf_verify($_GET['csrf_token'] ?? null)) {
    http_response_code(403);
    die('Security check failed (invalid or expired token). Please go back, refresh the page, and try again.');
}

$recId = (int)$_GET['id'];

// Fetch recommendation info
$stmt = $pdo->prepare("
    SELECT r.id, r.recommender_name, r.recommender_email, r.token, 
           s.first_name, s.last_name
    FROM recommendations r
    JOIN scholarship_applications s ON s.id = r.scholarship_application_id
    WHERE r.id = :id
");
$stmt->execute([':id' => $recId]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rec) {
    die("Recommendation not found.");
}

// Build email content
$token = $rec['token'];
$applicantName = $rec['first_name'] . ' ' . $rec['last_name'];
$link = "https://themorganlegacy.com/recommendation/submit_recommendation.php?token={$token}";

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
    $mail->setFrom('noreply@morganserver.com', 'TheMorganLegacy');
    $mail->addAddress($rec['recommender_email'], $rec['recommender_name']);

    // Content
    // Attach logo and give it a Content ID
$mail->AddEmbeddedImage('../assets/images/logo.png', 'logoimg');

// Then reference it in the HTML using cid:
$mail->isHTML(true);
$mail->Subject = "Recommendation Request for {$applicantName}";
$mail->Body = "
    <p><img src='cid:logoimg' alt='Morgan Legacy Scholarship Logo' style='height:80px;'></p>
    <p>Dear {$rec['recommender_name']},</p>
    <p>{$applicantName} has applied for The Morgan Legacy scholarship and listed you as a recommender.</p>
    <p>Please submit your recommendation using the link below:</p>
    <p><a href='{$link}' target='_blank'>Submit Recommendation</a></p>
    <p>Thank you for your support!</p>
    <p>The Morgan Legacy Scholarship Committee</p>
";
$mail->send();


    // Update recommendation status
    $update = $pdo->prepare("
        UPDATE recommendations
        SET status = 'sent', requested_date = NOW()
        WHERE id = :id
    ");
    $update->execute([':id' => $recId]);

    // echo "Recommendation email sent successfully!";
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
} catch (Exception $e) {
    echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
