<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sends the Final Review invite to a single committee member: the review
// link (gated by token) and the access code (the actual secret -- the link
// alone isn't enough). Never throws -- returns true/false so the caller can
// send to a whole batch of members without one failure stopping the rest.
function send_committee_review_email(array $config, string $email, string $name, string $token, string $code): bool {
    $link = "https://themorganlegacy.com/committee/review.php?token={$token}";

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
        $mail->addAddress($email, $name);

        $mail->AddEmbeddedImage(__DIR__ . '/../assets/images/logo.png', 'logoimg');
        $mail->isHTML(true);
        $mail->Subject = "Final Review is ready for your review";
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
                                    <p style='margin:0 0 16px;'>Dear {$name},</p>
                                    <p style='margin:0 0 16px;'>
                                        The applications currently in Final Review for The Morgan Legacy
                                        Scholarship are ready for your review. Please use the button below
                                        to view them.
                                    </p>
                                </td>
                            </tr>

                            <!-- CTA button -->
                            <tr>
                                <td align='center' style='padding: 0 40px 24px 40px;'>
                                    <table role='presentation' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='center' style='background-color:#C5A059; border-radius:6px;'>
                                                <a href='{$link}' target='_blank' style='display:inline-block; padding: 14px 32px; font-size:15px; font-weight:bold; color:#070537; text-decoration:none;'>
                                                    Review Applications
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Access code -->
                            <tr>
                                <td align='center' style='padding: 0 40px 32px 40px;'>
                                    <div style='background:#f8f8fa; border:1px solid #ececf1; border-radius:8px; padding: 18px 24px; display:inline-block;'>
                                        <div style='font-size:12px; color:#8a8a94; text-transform:uppercase; letter-spacing:0.05em; font-weight:bold; margin-bottom:6px;'>Your Access Code</div>
                                        <div style='font-size:26px; font-weight:bold; letter-spacing:0.25em; color:#070537;'>{$code}</div>
                                    </div>
                                    <p style='margin:14px 0 0; font-size:13px; color:#8a8a94; line-height:1.6;'>
                                        You'll be asked to enter this code after clicking the link above.
                                        This code is confidential -- please don't forward this email.
                                    </p>
                                </td>
                            </tr>

                            <!-- Sign-off -->
                            <tr>
                                <td style='padding: 0 40px 36px 40px; color:#212529; font-size:15px; line-height:1.7;'>
                                    <p style='margin:0 0 4px;'>Thank you for your time!</p>
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
        return true;
    } catch (Exception $e) {
        error_log("send_committee_review_email failed for {$email}: {$mail->ErrorInfo}");
        return false;
    }
}
