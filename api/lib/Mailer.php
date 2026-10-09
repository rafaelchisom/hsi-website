<?php

/**
 * Sends outgoing mail — via SmtpMailer when an SMTP host is configured in
 * Settings (the reliable path, needed on most real hosting since PHP's
 * built-in mail() is frequently disabled, unauthenticated, or spam-flagged),
 * falling back to mail() for local dev / hosts where it actually works. A
 * failure here is logged and swallowed; it must never break the request that
 * triggered it (e.g. a public contact-form submission).
 */
class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        return self::attempt($to, $subject, $body)['ok'];
    }

    /**
     * Same as send() but returns the underlying error message instead of
     * swallowing it — used by the admin "send test email" action so a
     * misconfigured host/port/credential shows up as an actionable message
     * rather than a silent failure.
     * @return array{ok:bool,error:?string}
     */
    public static function attempt(string $to, string $subject, string $body): array
    {
        if ($to === '') {
            return ['ok' => false, 'error' => 'No recipient address'];
        }
        $from = SiteSettings::get('mail_from', getenv('MAIL_FROM') ?: null)
            ?: ('noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        $smtpHost = SiteSettings::get('smtp_host');
        if ($smtpHost) {
            $config = [
                'host' => $smtpHost,
                'port' => (int) (SiteSettings::get('smtp_port', '587')),
                'username' => SiteSettings::get('smtp_username'),
                'password' => SiteSettings::get('smtp_secret'),
                'encryption' => SiteSettings::get('smtp_encryption', 'tls'),
                'from' => $from,
            ];
            try {
                $result = SmtpMailer::send($config, $to, $subject, $body);
            } catch (Throwable $e) {
                $result = ['ok' => false, 'error' => $e->getMessage()];
            }
            if (!$result['ok']) {
                error_log("[Mailer/SMTP] failed sending to $to — " . $result['error']);
            }
            return $result;
        }

        $headers = "From: $from\r\nContent-Type: text/plain; charset=UTF-8";
        try {
            $sent = @mail($to, $subject, $body, $headers);
            if (!$sent) {
                error_log("[Mailer] mail() returned false sending to $to — check server MTA configuration, or configure SMTP in Settings");
            }
            return ['ok' => $sent, 'error' => $sent ? null : "PHP's mail() function returned false — this is commonly disabled on shared hosting. Configure SMTP below instead."];
        } catch (Throwable $e) {
            error_log('[Mailer] ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
