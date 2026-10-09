<?php

/**
 * Minimal hand-rolled SMTP client (no Composer/PHPMailer dependency, matching
 * the rest of this project). Exists because PHP's built-in mail() — used by
 * Mailer.php when no SMTP host is configured — is unreliable on most shared
 * hosting (cPanel and similar): often disabled outright, unauthenticated so
 * it's flagged as spam, or simply not wired to a real mail transport. SMTP
 * lets an admin point at any real mailbox or transactional email provider
 * (the host's own mail server, Gmail, SendGrid, Mailgun, Postmark, ...) after
 * deploying to a new server, purely through site_settings — no code changes.
 *
 * Supports STARTTLS (typically port 587) and implicit TLS (typically port
 * 465) and AUTH LOGIN. Deliberately does not support attachments/HTML/CC —
 * this app only ever sends short plain-text notification emails.
 */
class SmtpMailer
{
    /**
     * @param array{host:string,port:int,username:?string,password:?string,encryption:string,from:string} $config
     * @return array{ok:bool,error:?string}
     */
    public static function send(array $config, string $to, string $subject, string $body): array
    {
        $host = $config['host'];
        $port = (int) ($config['port'] ?: 587);
        $encryption = $config['encryption'] ?: 'tls';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $from = $config['from'];

        $transport = $encryption === 'ssl' ? "ssl://$host:$port" : "tcp://$host:$port";
        $stream = @stream_socket_client($transport, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
        if (!$stream) {
            return ['ok' => false, 'error' => "Could not connect to $host:$port — $errstr"];
        }
        stream_set_timeout($stream, 15);

        try {
            [$code] = self::readResponse($stream);
            if ($code !== 220) {
                return ['ok' => false, 'error' => "Server did not greet with 220 (got $code)"];
            }

            $ehloName = parse_url($from, PHP_URL_HOST) ?: (self::hostPart($from) ?: 'localhost');

            $result = self::ehlo($stream, $ehloName);
            if (!$result['ok']) {
                return $result;
            }

            if ($encryption === 'tls') {
                self::command($stream, 'STARTTLS');
                [$code, $msg] = self::readResponse($stream);
                if ($code !== 220) {
                    return ['ok' => false, 'error' => "STARTTLS rejected: $msg"];
                }
                if (!@stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return ['ok' => false, 'error' => 'TLS handshake failed'];
                }
                $result = self::ehlo($stream, $ehloName);
                if (!$result['ok']) {
                    return $result;
                }
            }

            if ($username !== '') {
                self::command($stream, 'AUTH LOGIN');
                [$code, $msg] = self::readResponse($stream);
                if ($code !== 334) {
                    return ['ok' => false, 'error' => "AUTH LOGIN rejected: $msg"];
                }
                self::command($stream, base64_encode($username));
                [$code, $msg] = self::readResponse($stream);
                if ($code !== 334) {
                    return ['ok' => false, 'error' => "Username rejected: $msg"];
                }
                self::command($stream, base64_encode($password));
                [$code, $msg] = self::readResponse($stream);
                if ($code !== 235) {
                    return ['ok' => false, 'error' => "Authentication failed: $msg"];
                }
            }

            self::command($stream, "MAIL FROM:<$from>");
            [$code, $msg] = self::readResponse($stream);
            if ($code !== 250) {
                return ['ok' => false, 'error' => "MAIL FROM rejected: $msg"];
            }

            self::command($stream, "RCPT TO:<$to>");
            [$code, $msg] = self::readResponse($stream);
            if (!in_array($code, [250, 251], true)) {
                return ['ok' => false, 'error' => "RCPT TO rejected: $msg"];
            }

            self::command($stream, 'DATA');
            [$code, $msg] = self::readResponse($stream);
            if ($code !== 354) {
                return ['ok' => false, 'error' => "DATA rejected: $msg"];
            }

            $headers = [
                'From: ' . $from,
                'To: ' . $to,
                'Subject: ' . self::encodeHeader($subject),
                'Date: ' . date('r'),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];
            // SMTP requires CRLF line endings throughout DATA (RFC 5321 §2.3.7)
            // — callers pass plain PHP strings with bare \n, which a strict
            // server will never treat as a complete line, hanging the
            // connection waiting for a terminator that never arrives.
            $normalizedBody = str_replace("\r\n", "\n", $body);
            $normalizedBody = str_replace("\n", "\r\n", $normalizedBody);
            // Dot-stuffing per RFC 5321 §4.5.2 — a line starting with '.' must
            // be escaped or the SMTP server treats it as the end-of-data marker.
            $escapedBody = preg_replace('/^\./m', '..', $normalizedBody);
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.";
            self::command($stream, $message);
            [$code, $msg] = self::readResponse($stream);
            if ($code !== 250) {
                return ['ok' => false, 'error' => "Message rejected: $msg"];
            }

            self::command($stream, 'QUIT');
            return ['ok' => true, 'error' => null];
        } finally {
            fclose($stream);
        }
    }

    /** @return array{ok:bool,error:?string} */
    private static function ehlo($stream, string $name): array
    {
        self::command($stream, "EHLO $name");
        [$code, $msg] = self::readResponse($stream);
        if ($code !== 250) {
            return ['ok' => false, 'error' => "EHLO rejected: $msg"];
        }
        return ['ok' => true, 'error' => null];
    }

    private static function command($stream, string $line): void
    {
        // fwrite() over a network stream isn't guaranteed to write the whole
        // buffer in one call (more likely on longer message bodies) — loop
        // until it's all out rather than silently truncating the command.
        $data = $line . "\r\n";
        $total = strlen($data);
        $written = 0;
        while ($written < $total) {
            $n = fwrite($stream, substr($data, $written));
            if ($n === false || $n === 0) {
                break;
            }
            $written += $n;
        }
    }

    /** Reads a full (possibly multi-line, "250-...") SMTP response. @return array{0:int,1:string} */
    private static function readResponse($stream): array
    {
        $code = 0;
        $lines = [];
        while (($line = fgets($stream, 515)) !== false) {
            $lines[] = trim($line);
            $code = (int) substr($line, 0, 3);
            // A space (not a hyphen) after the code marks the final line.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        if ($lines === []) {
            return [0, 'No response from server (connection dropped or timed out)'];
        }
        return [$code, implode(' ', $lines)];
    }

    private static function encodeHeader(string $value): string
    {
        // Only ASCII subjects are expected in practice, but encode defensively
        // so a non-ASCII value can't corrupt the header block.
        return preg_match('/[^\x20-\x7E]/', $value)
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }

    private static function hostPart(string $email): ?string
    {
        $at = strrpos($email, '@');
        return $at !== false ? substr($email, $at + 1) : null;
    }
}
