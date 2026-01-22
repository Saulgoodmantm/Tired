<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Email Service
 * =============================================================================
 * SMTP email sending for OTP, notifications, and transactional emails
 * =============================================================================
 */

namespace App\Services;

class EmailService
{
    private string $host;
    private string $user;
    private string $pass;
    private int $port;
    private string $fromName;
    private string $fromEmail;

    public function __construct(array $config = [])
    {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $mailConfig = $config ?: $appConfig['mail'];

        $this->host = $mailConfig['host'] ?? 'smtp.gmail.com';
        $this->user = $mailConfig['user'] ?? '';
        $this->pass = $mailConfig['pass'] ?? '';
        $this->port = $mailConfig['port'] ?? 587;
        $this->fromName = $mailConfig['from_name'] ?? 'TiredOfDoinTM';
        $this->fromEmail = $mailConfig['from_email'] ?? $this->user;
    }

    /**
     * Send OTP verification email
     */
    public function sendOTP(string $to, string $otp): bool
    {
        $subject = "Your TiredOfDoinTM Verification Code: {$otp}";

        $html = $this->getEmailTemplate('otp', [
            'otp' => $otp,
            'expires_in' => '10 minutes',
        ]);

        $text = "Your TiredOfDoinTM verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";

        return $this->send($to, $subject, $html, $text);
    }

    /**
     * Send contact form notification to admin
     */
    public function sendContactNotification(string $name, string $email, string $subject, string $message): bool
    {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $adminEmails = $appConfig['admin']['emails'] ?? [];

        if (empty($adminEmails)) {
            error_log("No admin emails configured for contact notification");
            return false;
        }

        $emailSubject = "[TiredOfDoinTM] New Contact: {$subject}";

        $html = $this->getEmailTemplate('contact_notification', [
            'name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'subject' => htmlspecialchars($subject),
            'message' => nl2br(htmlspecialchars($message)),
            'date' => date('F j, Y \a\t g:i A'),
        ]);

        $text = "New contact form submission:\n\nFrom: {$name} <{$email}>\nSubject: {$subject}\n\nMessage:\n{$message}\n\nReceived: " . date('F j, Y \a\t g:i A');

        $success = true;
        foreach ($adminEmails as $adminEmail) {
            if (!$this->send($adminEmail, $emailSubject, $html, $text)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation(string $to, array $booking): bool
    {
        $subject = "Booking Confirmed - TiredOfDoinTM";

        $html = $this->getEmailTemplate('booking_confirmation', [
            'booking_id' => $booking['id'] ?? 'N/A',
            'date' => date('F j, Y', strtotime($booking['date_start'] ?? 'now')),
            'time' => date('g:i A', strtotime($booking['date_start'] ?? 'now')),
            'duration' => ($booking['duration_hours'] ?? 2) . ' hours',
            'type' => ucfirst($booking['service_type'] ?? 'personal'),
            'total' => number_format($booking['total_price'] ?? 0, 2),
            'deposit' => number_format($booking['deposit_amount'] ?? 0, 2),
            'unique_link' => $booking['unique_link'] ?? '',
        ]);

        $text = "Your booking has been confirmed!\n\n"
            . "Date: " . date('F j, Y', strtotime($booking['date_start'] ?? 'now')) . "\n"
            . "Time: " . date('g:i A', strtotime($booking['date_start'] ?? 'now')) . "\n"
            . "Duration: " . ($booking['duration_hours'] ?? 2) . " hours\n"
            . "Type: " . ucfirst($booking['service_type'] ?? 'personal') . "\n"
            . "Total: $" . number_format($booking['total_price'] ?? 0, 2) . "\n\n"
            . "Thank you for booking with TiredOfDoinTM!";

        return $this->send($to, $subject, $html, $text);
    }

    /**
     * Send booking reminder email
     */
    public function sendBookingReminder(string $to, array $booking, int $hoursUntil): bool
    {
        $subject = "Reminder: Your Session in {$hoursUntil} Hours - TiredOfDoinTM";

        $html = $this->getEmailTemplate('booking_reminder', [
            'hours_until' => $hoursUntil,
            'date' => date('F j, Y', strtotime($booking['date_start'] ?? 'now')),
            'time' => date('g:i A', strtotime($booking['date_start'] ?? 'now')),
            'type' => ucfirst($booking['service_type'] ?? 'personal'),
            'location' => $booking['location'] ?? 'TBD',
        ]);

        $text = "Reminder: Your photography session is in {$hoursUntil} hours!\n\n"
            . "Date: " . date('F j, Y', strtotime($booking['date_start'] ?? 'now')) . "\n"
            . "Time: " . date('g:i A', strtotime($booking['date_start'] ?? 'now')) . "\n\n"
            . "We look forward to seeing you!\n\n"
            . "- TiredOfDoinTM";

        return $this->send($to, $subject, $html, $text);
    }

    /**
     * Send gallery ready notification
     */
    public function sendGalleryReady(string $to, string $galleryTitle, string $galleryLink): bool
    {
        $subject = "Your Gallery is Ready! - TiredOfDoinTM";

        $html = $this->getEmailTemplate('gallery_ready', [
            'gallery_title' => htmlspecialchars($galleryTitle),
            'gallery_link' => $galleryLink,
        ]);

        $text = "Great news! Your gallery \"{$galleryTitle}\" is now ready for viewing.\n\n"
            . "View your gallery: {$galleryLink}\n\n"
            . "Thank you for choosing TiredOfDoinTM!";

        return $this->send($to, $subject, $html, $text);
    }

    /**
     * Send email via SMTP
     */
    public function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        if (empty($this->user) || empty($this->pass)) {
            error_log("Email not configured - would send to {$to}: {$subject}");
            return false;
        }

        $boundary = md5(time());

        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Reply-To' => $this->fromEmail,
            'MIME-Version' => '1.0',
            'Content-Type' => "multipart/alternative; boundary=\"{$boundary}\"",
            'X-Mailer' => 'TiredOfDoinTM/1.0',
        ];

        // Build multipart message
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= ($text ?? strip_tags($html)) . "\r\n\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $html . "\r\n\r\n";
        $message .= "--{$boundary}--";

        // Try using PHP mail() first (if configured)
        if (function_exists('mail')) {
            $headerStr = '';
            foreach ($headers as $key => $value) {
                $headerStr .= "{$key}: {$value}\r\n";
            }

            $result = @mail($to, $subject, $message, $headerStr);
            if ($result) {
                return true;
            }
        }

        // Fall back to direct SMTP
        return $this->sendViaSMTP($to, $subject, $message, $headers);
    }

    /**
     * Send email directly via SMTP socket
     */
    private function sendViaSMTP(string $to, string $subject, string $body, array $headers): bool
    {
        try {
            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 30);

            if (!$socket) {
                error_log("SMTP Connection failed: {$errstr} ({$errno})");
                return false;
            }

            // Read greeting
            $this->smtpRead($socket);

            // EHLO
            $this->smtpWrite($socket, "EHLO " . gethostname());
            $this->smtpRead($socket);

            // STARTTLS for port 587
            if ($this->port === 587) {
                $this->smtpWrite($socket, "STARTTLS");
                $this->smtpRead($socket);

                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

                $this->smtpWrite($socket, "EHLO " . gethostname());
                $this->smtpRead($socket);
            }

            // AUTH LOGIN
            $this->smtpWrite($socket, "AUTH LOGIN");
            $this->smtpRead($socket);

            $this->smtpWrite($socket, base64_encode($this->user));
            $this->smtpRead($socket);

            $this->smtpWrite($socket, base64_encode($this->pass));
            $response = $this->smtpRead($socket);

            if (strpos($response, '235') === false) {
                error_log("SMTP Auth failed: {$response}");
                fclose($socket);
                return false;
            }

            // MAIL FROM
            $this->smtpWrite($socket, "MAIL FROM:<{$this->fromEmail}>");
            $this->smtpRead($socket);

            // RCPT TO
            $this->smtpWrite($socket, "RCPT TO:<{$to}>");
            $this->smtpRead($socket);

            // DATA
            $this->smtpWrite($socket, "DATA");
            $this->smtpRead($socket);

            // Headers
            $data = "To: {$to}\r\n";
            $data .= "Subject: {$subject}\r\n";
            foreach ($headers as $key => $value) {
                $data .= "{$key}: {$value}\r\n";
            }
            $data .= "\r\n{$body}\r\n.";

            $this->smtpWrite($socket, $data);
            $response = $this->smtpRead($socket);

            // QUIT
            $this->smtpWrite($socket, "QUIT");
            fclose($socket);

            return strpos($response, '250') !== false;
        } catch (\Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    private function smtpWrite($socket, string $data): void
    {
        fwrite($socket, $data . "\r\n");
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Get email template HTML
     */
    private function getEmailTemplate(string $template, array $vars): string
    {
        $baseStyle = '
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0a0a0a; color: #ffffff; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #111111; border-radius: 12px; padding: 40px; }
            .logo { text-align: center; margin-bottom: 30px; font-size: 24px; font-weight: bold; }
            .content { line-height: 1.6; }
            .otp-code { font-size: 36px; font-weight: bold; text-align: center; letter-spacing: 8px; padding: 20px; background: #1a1a1a; border-radius: 8px; margin: 20px 0; }
            .button { display: inline-block; background: #ffffff; color: #000000; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 20px 0; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #333; font-size: 12px; color: #888; text-align: center; }
            .info-row { padding: 10px 0; border-bottom: 1px solid #222; }
            .info-label { color: #888; }
            .info-value { font-weight: 600; }
        ';

        switch ($template) {
            case 'otp':
                return "
                    <html><head><style>{$baseStyle}</style></head><body>
                    <div class='container'>
                        <div class='logo'>TiredOfDoinTM</div>
                        <div class='content'>
                            <p>Here's your verification code:</p>
                            <div class='otp-code'>{$vars['otp']}</div>
                            <p>This code expires in {$vars['expires_in']}.</p>
                            <p>If you didn't request this code, you can safely ignore this email.</p>
                        </div>
                        <div class='footer'>
                            © " . date('Y') . " TiredOfDoinTM. All rights reserved.
                        </div>
                    </div>
                    </body></html>
                ";

            case 'contact_notification':
                return "
                    <html><head><style>{$baseStyle}</style></head><body>
                    <div class='container'>
                        <div class='logo'>TiredOfDoinTM</div>
                        <div class='content'>
                            <h2>New Contact Form Submission</h2>
                            <div class='info-row'><span class='info-label'>From:</span> <span class='info-value'>{$vars['name']}</span></div>
                            <div class='info-row'><span class='info-label'>Email:</span> <span class='info-value'>{$vars['email']}</span></div>
                            <div class='info-row'><span class='info-label'>Subject:</span> <span class='info-value'>{$vars['subject']}</span></div>
                            <div class='info-row'><span class='info-label'>Received:</span> <span class='info-value'>{$vars['date']}</span></div>
                            <h3>Message:</h3>
                            <div style='background: #1a1a1a; padding: 20px; border-radius: 8px;'>{$vars['message']}</div>
                        </div>
                    </div>
                    </body></html>
                ";

            case 'booking_confirmation':
                $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
                $appUrl = $appConfig['app']['url'] ?? 'https://tiredofdointm.com';
                return "
                    <html><head><style>{$baseStyle}</style></head><body>
                    <div class='container'>
                        <div class='logo'>TiredOfDoinTM</div>
                        <div class='content'>
                            <h2>Booking Confirmed! 🎉</h2>
                            <p>Your photography session has been booked. Here are your details:</p>
                            <div class='info-row'><span class='info-label'>Date:</span> <span class='info-value'>{$vars['date']}</span></div>
                            <div class='info-row'><span class='info-label'>Time:</span> <span class='info-value'>{$vars['time']}</span></div>
                            <div class='info-row'><span class='info-label'>Duration:</span> <span class='info-value'>{$vars['duration']}</span></div>
                            <div class='info-row'><span class='info-label'>Session Type:</span> <span class='info-value'>{$vars['type']}</span></div>
                            <div class='info-row'><span class='info-label'>Total:</span> <span class='info-value'>\${$vars['total']}</span></div>
                            <div class='info-row'><span class='info-label'>Deposit Paid:</span> <span class='info-value'>\${$vars['deposit']}</span></div>
                            <p style='text-align: center; margin-top: 30px;'>
                                <a href='{$appUrl}/booking/{$vars['unique_link']}' class='button'>View Booking Details</a>
                            </p>
                        </div>
                        <div class='footer'>
                            Questions? Reply to this email or contact us at contact@tiredofdointm.com
                        </div>
                    </div>
                    </body></html>
                ";

            case 'booking_reminder':
                return "
                    <html><head><style>{$baseStyle}</style></head><body>
                    <div class='container'>
                        <div class='logo'>TiredOfDoinTM</div>
                        <div class='content'>
                            <h2>Session Reminder ⏰</h2>
                            <p>Your photography session is coming up in <strong>{$vars['hours_until']} hours</strong>!</p>
                            <div class='info-row'><span class='info-label'>Date:</span> <span class='info-value'>{$vars['date']}</span></div>
                            <div class='info-row'><span class='info-label'>Time:</span> <span class='info-value'>{$vars['time']}</span></div>
                            <div class='info-row'><span class='info-label'>Session Type:</span> <span class='info-value'>{$vars['type']}</span></div>
                            <div class='info-row'><span class='info-label'>Location:</span> <span class='info-value'>{$vars['location']}</span></div>
                            <p>We're excited to see you!</p>
                        </div>
                        <div class='footer'>
                            Need to reschedule? Contact us ASAP at contact@tiredofdointm.com
                        </div>
                    </div>
                    </body></html>
                ";

            case 'gallery_ready':
                return "
                    <html><head><style>{$baseStyle}</style></head><body>
                    <div class='container'>
                        <div class='logo'>TiredOfDoinTM</div>
                        <div class='content'>
                            <h2>Your Gallery is Ready! 📸</h2>
                            <p>Great news! Your photos from <strong>{$vars['gallery_title']}</strong> are now available for viewing.</p>
                            <p style='text-align: center; margin: 30px 0;'>
                                <a href='{$vars['gallery_link']}' class='button'>View Your Gallery</a>
                            </p>
                            <p>Thank you for choosing TiredOfDoinTM for your photography needs!</p>
                        </div>
                        <div class='footer'>
                            © " . date('Y') . " TiredOfDoinTM. All rights reserved.
                        </div>
                    </div>
                    </body></html>
                ";

            default:
                return "<html><body><p>{$template}</p></body></html>";
        }
    }
}
