<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

final class Mailer
{
    /**
     * Sends an email via SMTP if configured; silently logs and no-ops
     * in local/dev environments where SMTP_HOST is not set, so the
     * rest of the flow (e.g. contact form, registration) still succeeds.
     *
     * @param array<int, array{content:string, filename:string}> $attachments
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, array $attachments = []): bool
    {
        // Settings saved via the admin panel override the env-based defaults; a fresh
        // deploy with no settings rows yet just falls back to the SMTP_* constants.
        $settings = new Setting();
        $host = $settings->get('smtp_host', SMTP_HOST);
        $port = $settings->get('smtp_port', (string) SMTP_PORT);
        $user = $settings->get('smtp_user', SMTP_USER);
        $pass = $settings->get('smtp_pass', SMTP_PASS);
        $fromEmail = $settings->get('smtp_from_email', SMTP_FROM_EMAIL);
        $fromName = $settings->get('smtp_from_name', SMTP_FROM_NAME);

        if ($host === '') {
            error_log("Mailer: SMTP not configured, skipping email to {$toEmail} — subject: {$subject}");
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $port;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            foreach ($attachments as $attachment) {
                $mail->addStringAttachment($attachment['content'], $attachment['filename']);
            }

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('Mailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
