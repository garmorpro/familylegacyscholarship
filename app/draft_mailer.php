<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sends an applicant their unique link to resume a saved-but-not-yet-submitted
// application (see app/save_draft.php). Never throws -- returns true/false,
// meant to be checked by the caller since this email IS the deliverable of
// a "Save & Finish Later" click (unlike the other applicant emails on this
// site, which are best-effort follow-ups to an already-saved submission).
function send_draft_resume_email(array $config, string $email, string $firstName, string $resumeLink): bool {
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
        $mail->Subject = "Your saved Morgan Legacy Scholarship application";

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
                                        You saved your progress on a Morgan Legacy Scholarship application.
                                        Use the button below whenever you're ready to pick up where you left off.
                                    </p>
                                </td>
                            </tr>

                            <!-- CTA button -->
                            <tr>
                                <td align='center' style='padding: 0 40px 24px 40px;'>
                                    <table role='presentation' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='center' style='background-color:#C5A059; border-radius:6px;'>
                                                <a href='{$resumeLink}' target='_blank' style='display:inline-block; padding: 14px 32px; font-size:15px; font-weight:bold; color:#070537; text-decoration:none;'>
                                                    Finish My Application
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td style='padding: 0 40px 36px 40px; color:#212529; font-size:15px; line-height:1.7;'>
                                    <p style='margin:0 0 16px;'>
                                        This link is unique to you and only saves your progress -- nothing is
                                        submitted for review until you click Submit on the finished application.
                                    </p>
                                    <p style='margin:0 0 4px;'>Thank you for applying!</p>
                                    <p style='margin:0;'>The Morgan Legacy Scholarship</p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color:#f8f8fa; padding: 18px 40px; border-top:1px solid #ececf1;'>
                                    <p style='margin:0; font-size:12px; color:#8a8a94; line-height:1.6;'>
                                        If the button above doesn't work, copy and paste this link into your browser:<br>
                                        <a href='{$resumeLink}' target='_blank' style='color:#8a8a94;'>{$resumeLink}</a>
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
        error_log("send_draft_resume_email failed for {$email}: {$mail->ErrorInfo}");
        return false;
    }
}
