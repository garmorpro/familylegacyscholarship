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
        <!DOCTYPE html>
        <html>
        <body style='margin:0; padding:0; background-color:#f2f2f5; font-family: Arial, Helvetica, sans-serif;'>
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#f2f2f5; padding: 32px 16px;'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden;'>

                            <!-- Header -->
                            <tr>
                                <td align='center' style='background-color:#070537; padding: 28px 24px;'>
                                    <img src='cid:logoimg' alt='Morgan Legacy Scholarship' style='height:64px; display:block;'>
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style='padding: 36px 40px 10px 40px; color:#212529; font-size:15px; line-height:1.7;'>
                                    <p style='margin:0 0 16px;'>Dear {$rec['recommender_name']},</p>
                                    <p style='margin:0 0 16px;'>
                                        <strong>{$applicantName}</strong> has applied for The Morgan Legacy Scholarship
                                        and listed you as a recommender. We'd be grateful if you could share a letter
                                        of recommendation on their behalf.
                                    </p>
                                    <p style='margin:0 0 28px;'>
                                        Please use the button below to submit your recommendation online &mdash;
                                        it only takes a few minutes.
                                    </p>
                                </td>
                            </tr>

                            <!-- CTA button -->
                            <tr>
                                <td align='center' style='padding: 0 40px 32px 40px;'>
                                    <table role='presentation' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='center' style='background-color:#C5A059; border-radius:6px;'>
                                                <a href='{$link}' target='_blank' style='display:inline-block; padding: 14px 32px; font-size:15px; font-weight:bold; color:#070537; text-decoration:none;'>
                                                    Submit Recommendation
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Sign-off -->
                            <tr>
                                <td style='padding: 0 40px 36px 40px; color:#212529; font-size:15px; line-height:1.7;'>
                                    <p style='margin:0 0 4px;'>Thank you for your support!</p>
                                    <p style='margin:0;'>The Morgan Legacy Scholarship Committee</p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color:#f8f8fa; padding: 18px 40px; border-top:1px solid #ececf1;'>
                                    <p style='margin:0; font-size:12px; color:#8a8a94; line-height:1.6;'>
                                        If the button above doesn't work, copy and paste this link into your browser:<br>
                                        <a href='{$link}' target='_blank' style='color:#8a8a94;'>{$link}</a>
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
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
