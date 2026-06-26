<?php
/**
 * Lightweight SMTP Mailer
 */
function send_smtp_email($to, $subject, $message, $settings) {
    $host = $settings['smtp_host'] ?? '';
    $port = (int)($settings['smtp_port'] ?? 465);
    $user = $settings['smtp_user'] ?? '';
    $pass = $settings['smtp_pass'] ?? '';
    $from = $settings['smtp_from'] ?? $user;
    
    if (!$host || !$user || !$pass || !$to) {
        return false;
    }

    $crlf = "\r\n";
    
    // Construct Email Headers
    $headers = [
        "From: HSI <$from>",
        "To: <$to>",
        "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Date: " . date("r")
    ];
    $headerStr = implode($crlf, $headers) . $crlf . $crlf;
    $payload = $headerStr . $message . $crlf . ".";

    $timeout = 10;
    
    if ($port == 465) {
        $socket = fsockopen("ssl://" . $host, $port, $errno, $errstr, $timeout);
    } else {
        $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
    }

    if (!$socket) {
        error_log("SMTP Error: Could not connect to $host:$port - $errstr");
        return false;
    }

    $res = fread($socket, 512);

    function send_cmd($socket, $cmd, $crlf) {
        fputs($socket, $cmd . $crlf);
        return fread($socket, 512);
    }

    send_cmd($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), $crlf);

    if ($port != 465 && $port == 587) {
        $res = send_cmd($socket, "STARTTLS", $crlf);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        send_cmd($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), $crlf);
    }

    send_cmd($socket, "AUTH LOGIN", $crlf);
    send_cmd($socket, base64_encode($user), $crlf);
    $res = send_cmd($socket, base64_encode($pass), $crlf);

    if (substr($res, 0, 3) != '235') {
        error_log("SMTP Auth Failed: " . $res);
        fclose($socket);
        return false;
    }

    send_cmd($socket, "MAIL FROM:<$from>", $crlf);
    send_cmd($socket, "RCPT TO:<$to>", $crlf);
    send_cmd($socket, "DATA", $crlf);
    
    fputs($socket, $payload . $crlf);
    $res = fread($socket, 512);

    send_cmd($socket, "QUIT", $crlf);
    fclose($socket);
    
    return substr($res, 0, 3) == '250';
}
