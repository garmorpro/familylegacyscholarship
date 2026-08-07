<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Confirms to the applicant themselves that their application was
// received, and tells them the review window from Settings > Timeline
// so they know what to expect next -- separate from the recommender
// request email, which goes to whoever they named as their recommender,
// not to the applicant. Never throws -- returns true/false, meant to be
// called best-effort so a failure here never blocks the submission
// itself (the application is already safely saved by the time this runs).
function send_application_received_email(array $config, string $email, string $firstName, ?string $reviewStart, ?string $reviewEnd): bool {
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
        $mail->addAddress($email, $firstName);

        $mail->AddEmbeddedImage(__DIR__ . '/../assets/images/logo.png', 'logoimg');
        $mail->isHTML(true);
        $mail->Subject = "We've received your Morgan Legacy Scholarship application";

        // Both dates need to actually be set for the range to mean
        // anything -- falls back to a generic line rather than showing a
        // broken/partial date if Settings > Timeline hasn't been filled
        // in yet for this cycle.
        if (!empty($reviewStart) && !empty($reviewEnd)) {
            $reviewWindow = date('F j, Y', strtotime($reviewStart)) . ' &ndash; ' . date('F j, Y', strtotime($reviewEnd));
            $reviewLine = "Our committee will begin reviewing applications between <strong>{$reviewWindow}</strong>.";
        } else {
            $reviewLine = "We'll follow up once our review timeline for this cycle is finalized.";
        }

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
                                    <p style='margin:0 0 16px;'>Dear {$firstName},</p>
                                    <p style='margin:0 0 16px;'>
                                        Thank you for applying to The Morgan Legacy Scholarship! We've
                                        successfully received your application.
                                    </p>
                                    <p style='margin:0 0 16px;'>
                                        {$reviewLine}
                                    </p>
                                    <p style='margin:0 0 16px;'>
                                        We've also reached out to the recommender you provided to request
                                        their letter of recommendation on your behalf -- no further action
                                        is needed from you there.
                                    </p>
                                </td>
                            </tr>

                            <!-- Sign-off -->
                            <tr>
                                <td style='padding: 0 40px 36px 40px; color:#212529; font-size:15px; line-height:1.7;'>
                                    <p style='margin:0 0 4px;'>Thank you again for applying!</p>
                                    <p style='margin:0;'>The Morgan Legacy Scholarship</p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color:#f8f8fa; padding: 18px 40px; border-top:1px solid #ececf1;'>
                                    <p style='margin:0; font-size:12px; color:#8a8a94; line-height:1.6;'>
                                        Questions? Reach out anytime at
                                        <a href='mailto:scholarship@themorganlegacy.com' style='color:#8a8a94;'>scholarship@themorganlegacy.com</a>.
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
        return true;
    } catch (Exception $e) {
        error_log("send_application_received_email failed for {$email}: {$mail->ErrorInfo}");
        return false;
    }
}
