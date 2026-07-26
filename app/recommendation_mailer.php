<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sends the recommendation-request email for a single recommendation row and
// marks it 'sent' on success. Never throws — returns true/false so callers
// (auto-send on submit, the manual admin button, the cron batch sender) can
// each decide how to handle a failure without duplicating the mail setup.
function send_recommendation_request_email(PDO $pdo, array $config, int $recommendationId): bool {
    $stmt = $pdo->prepare("
        SELECT r.id, r.recommender_name, r.recommender_email, r.token,
               s.first_name, s.last_name
        FROM recommendations r
        JOIN scholarship_applications s ON s.id = r.scholarship_application_id
        WHERE r.id = :id
    ");
    $stmt->execute([':id' => $recommendationId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) {
        error_log("send_recommendation_request_email: recommendation {$recommendationId} not found");
        return false;
    }

    $applicantName = $rec['first_name'] . ' ' . $rec['last_name'];
    $link = "https://themorganlegacy.com/recommendation/submit_recommendation.php?token={$rec['token']}";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp']['username'];
        $mail->Password   = $config['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['smtp']['port'];

        $mail->setFrom('scholarship@themorganlegacy.com', 'The Morgan Legacy');
        $mail->addAddress($rec['recommender_email'], $rec['recommender_name']);

        $mail->AddEmbeddedImage(__DIR__ . '/../assets/images/logo.png', 'logoimg');
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

        $update = $pdo->prepare("
            UPDATE recommendations
            SET status = 'sent', requested_date = NOW()
            WHERE id = :id
        ");
        $update->execute([':id' => $recommendationId]);

        return true;
    } catch (Exception $e) {
        error_log("send_recommendation_request_email failed for recommendation {$recommendationId}: {$mail->ErrorInfo}");
        return false;
    }
}
