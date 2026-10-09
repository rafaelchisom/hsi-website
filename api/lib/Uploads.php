<?php

/**
 * Where uploaded images live. With SUPABASE_URL + SUPABASE_SERVICE_KEY set they
 * go to a public Supabase Storage bucket — required on Render, whose local
 * disk is wiped on every deploy and restart. Without them they stay in
 * api/uploads/ (local dev, or a host with a persistent disk).
 */
class Uploads
{
    public const LOCAL_DIR = __DIR__ . '/../uploads/';

    public static function usesSupabase(): bool
    {
        return SUPABASE_URL !== '' && SUPABASE_SERVICE_KEY !== '';
    }

    public static function url(string $filename): string
    {
        if (self::usesSupabase()) {
            return SUPABASE_URL . '/storage/v1/object/public/' . rawurlencode(SUPABASE_BUCKET) . '/' . rawurlencode($filename);
        }
        return SITE_PATH . '/api/uploads/' . $filename;
    }

    /**
     * Moves a finished file from api/uploads/ to Supabase Storage (no-op when
     * storing locally). Returns an error message, or null on success.
     */
    public static function publish(string $filename, string $mime): ?string
    {
        if (!self::usesSupabase()) {
            return null;
        }
        $path = self::LOCAL_DIR . $filename;
        [$status, $body] = self::request('POST', '/storage/v1/object/' . rawurlencode(SUPABASE_BUCKET) . '/' . rawurlencode($filename), file_get_contents($path), [
            'Content-Type: ' . $mime,
            'Cache-Control: max-age=31536000',
            'x-upsert: true',
        ]);
        if ($status < 200 || $status >= 300) {
            return "Supabase Storage upload failed (HTTP $status): $body";
        }
        @unlink($path);
        return null;
    }

    public static function delete(string $filename): void
    {
        if (self::usesSupabase()) {
            [$status, $body] = self::request('DELETE', '/storage/v1/object/' . rawurlencode(SUPABASE_BUCKET) . '/' . rawurlencode($filename));
            if ($status >= 300 && $status !== 404) {
                error_log("[Uploads] delete $filename failed (HTTP $status): $body");
            }
            return;
        }
        $path = self::LOCAL_DIR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /** Creates the public bucket if it doesn't exist yet. Returns an error message, or null. */
    public static function ensureBucket(): ?string
    {
        [$status] = self::request('GET', '/storage/v1/bucket/' . rawurlencode(SUPABASE_BUCKET));
        if ($status === 200) {
            return null;
        }
        [$status, $body] = self::request('POST', '/storage/v1/bucket', json_encode([
            'id' => SUPABASE_BUCKET,
            'name' => SUPABASE_BUCKET,
            'public' => true,
        ]), ['Content-Type: application/json']);
        return ($status >= 200 && $status < 300) ? null : "Could not create bucket (HTTP $status): $body";
    }

    /** @return array{0:int,1:string} [http_status, body] */
    private static function request(string $method, string $path, ?string $body = null, array $headers = []): array
    {
        $headers[] = 'apikey: ' . SUPABASE_SERVICE_KEY;
        // Legacy service_role keys are JWTs and go in Authorization too; the
        // newer sb_secret_ keys are accepted in the apikey header alone.
        if (str_starts_with(SUPABASE_SERVICE_KEY, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . SUPABASE_SERVICE_KEY;
        }
        $ch = curl_init(SUPABASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [$status, $raw === false ? $error : (string) $raw];
    }
}
