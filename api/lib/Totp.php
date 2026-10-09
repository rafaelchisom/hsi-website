<?php

/**
 * TOTP (RFC 6238) / HOTP (RFC 4226) — hand-rolled, no dependency, authenticator-app
 * compatible (Google Authenticator, Authy, 1Password, etc.). SHA1/6-digit/30s
 * period is the universal default those apps expect.
 */
class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function provisioningUri(string $secret, string $accountEmail, string $issuer = 'DHAF Admin'): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountEmail);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
        return "otpauth://totp/{$label}?{$query}";
    }

    /** $window = how many 30s steps of clock drift either side to tolerate. */
    public static function verify(string $base32Secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $timeStep = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($base32Secret, $timeStep + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function hotp(string $base32Secret, int $counter): string
    {
        $key = self::base32Decode($base32Secret);
        $data = pack('N*', 0, $counter); // 8-byte big-endian counter
        $hash = hash_hmac('sha1', $data, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);
        $code = $binary % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= self::ALPHABET[bindec($chunk)];
        }
        return $output;
    }

    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));
        $bits = '';
        foreach (str_split($b32) as $char) {
            $val = strpos(self::ALPHABET, $char);
            if ($val === false) {
                continue;
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) < 8) {
                continue; // drop incomplete trailing padding bits
            }
            $bytes .= chr(bindec($byte));
        }
        return $bytes;
    }
}
