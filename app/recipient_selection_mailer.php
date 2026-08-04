<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sends the "you've been selected" email to this cycle's final recipient.
// Called only from cron/send_selection_emails.php, once a recipient's
// scheduled send time has arrived -- never called directly from the admin
// designation flow. Never throws -- returns true/false so the cron script
// can keep going if one send fails.
function send_recipient_selection_email(array $config, string $email, string $firstName, string $awardAmount): bool {
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
        $mail->Subject = "Congratulations -- you've been selected!";

        $awardLine = $awardAmount !== ''
            ? "<p style='margin:0 0 16px;'>You have been selected as this year's Morgan Legacy Scholarship recipient, awarded <strong>\$" . number_format((float) $awardAmount) . "</strong>.</p>"
            : "<p style='margin:0 0 16px;'>You have been selected as this year's Morgan Legacy Scholarship recipient.</p>";

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
                                    <p style='margin:0 0 16px; font-size:19px; font-weight:bold; color:#070537;'>Congratulations!</p>
                                    {$awardLine}
                                    <p style='margin:0 0 16px;'>
                                        Thank you for the time and effort you put into your application -- it truly
                                        stood out to our committee. Someone from The Morgan Legacy Scholarship will
                                        be in touch shortly with next steps.
                                    </p>
                                </td>
                            </tr>

                            <!-- Sign-off -->
                            <tr>
                                <td style='padding: 0 40px 36px 40px; color:#212529; font-size:15px; line-height:1.7;'>
                                    <p style='margin:0 0 4px;'>Congratulations again!</p>
                                    <p style='margin:0;'>The Morgan Legacy Scholarship Committee</p>
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
        error_log("send_recipient_selection_email failed for {$email}: {$mail->ErrorInfo}");
        return false;
    }
}
