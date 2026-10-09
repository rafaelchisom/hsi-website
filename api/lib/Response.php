<?php

class Response
{
    public static function json($data, int $status = 200): void
    {
        self::securityHeaders();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['error' => $message], $status);
    }

    public static function xml(string $xml, int $status = 200): void
    {
        self::securityHeaders();
        http_response_code($status);
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
        exit;
    }

    public static function csv(string $csv, string $filename, int $status = 200): void
    {
        self::securityHeaders();
        http_response_code($status);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        echo $csv;
        exit;
    }

    private static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
