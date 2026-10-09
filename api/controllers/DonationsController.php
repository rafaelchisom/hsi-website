<?php

/**
 * PayPal Checkout (Orders API v2) integration for the "full checkout" donation
 * mode. Client ID/Secret live in site_settings (paypal_client_id / paypal_secret,
 * the latter excluded from the public GET /site-settings response — see
 * SiteSettingsController). Talks to PayPal's sandbox API unless the
 * paypal_mode setting is "live" (Settings → Donations).
 *
 * PayPal's v2 Orders API only handles one-time payments. A "monthly" pledge
 * is recorded as such for the admin's records, but actually billing it every
 * month would require a separate Subscriptions product/plan configured on the
 * PayPal account first — not something that can be provisioned from here, so
 * the frontend must be honest with donors that the first charge is one-time.
 */
class DonationsController
{
    private const PAYPAL_API_SANDBOX = 'https://api-m.sandbox.paypal.com';
    private const PAYPAL_API_LIVE = 'https://api-m.paypal.com';
    private const DEFAULT_MIN_AMOUNT = 1;
    private const DEFAULT_MAX_AMOUNT = 100000;

    /** Settings → Donations → PayPal mode. Must match the kind of Client ID/Secret entered there. */
    private static function apiBase(): string
    {
        return SiteSettings::get('paypal_mode') === 'live' ? self::PAYPAL_API_LIVE : self::PAYPAL_API_SANDBOX;
    }

    private static function minAmount(): float
    {
        return (float) SiteSettings::get('donate_min_amount', (string) self::DEFAULT_MIN_AMOUNT);
    }

    private static function maxAmount(): float
    {
        return (float) SiteSettings::get('donate_max_amount', (string) self::DEFAULT_MAX_AMOUNT);
    }

    public static function routes(Router $r): void
    {
        $r->post('/donations/paypal/create-order', [self::class, 'createOrder']);
        $r->post('/donations/paypal/capture-order', [self::class, 'captureOrder']);
        $r->get('/donations', [self::class, 'index']);
    }

    public static function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::get();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));
        $total = (int) $pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn();
        $stmt = $pdo->prepare('SELECT * FROM donations ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        Response::json([
            'data' => $stmt->fetchAll(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
    }

    public static function createOrder(): void
    {
        RateLimiter::enforce('donate', 15, 60);
        $data = Validate::jsonBody();
        $amount = (float) ($data['amount'] ?? 0);
        $frequency = ($data['frequency'] ?? 'once') === 'monthly' ? 'monthly' : 'once';
        $dedication = trim((string) ($data['dedication'] ?? ''));
        if (mb_strlen($dedication) > 500) {
            $dedication = mb_substr($dedication, 0, 500);
        }
        $min = self::minAmount();
        $max = self::maxAmount();
        if ($amount < $min || $amount > $max) {
            Response::error('Enter an amount between $' . $min . ' and $' . number_format($max), 422);
        }

        [$clientId, $secret] = self::credentials();
        $token = self::getAccessToken($clientId, $secret);

        $amountStr = number_format($amount, 2, '.', '');
        [$status, $body] = self::request('POST', '/v2/checkout/orders', $token, [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => ['currency_code' => 'USD', 'value' => $amountStr],
                'description' => 'Donation to Digital Healthcare Access Foundation' . ($frequency === 'monthly' ? ' (monthly)' : ''),
            ]],
        ]);

        if ($status >= 300 || empty($body['id'])) {
            error_log('PayPal create-order failed: ' . json_encode($body));
            Response::error('Could not start PayPal checkout. Please try again.', 502);
        }

        $pdo = Database::get();
        $pdo->prepare('INSERT INTO donations (provider, provider_order_id, status, frequency, amount, currency, dedication)
            VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute(['paypal', $body['id'], 'created', $frequency, $amountStr, 'USD', $dedication !== '' ? $dedication : null]);

        Response::json(['id' => $body['id']]);
    }

    public static function captureOrder(): void
    {
        $data = Validate::jsonBody();
        Validate::requireFields($data, ['orderID']);
        $orderId = (string) $data['orderID'];

        [$clientId, $secret] = self::credentials();
        $token = self::getAccessToken($clientId, $secret);

        [$status, $body] = self::request('POST', "/v2/checkout/orders/$orderId/capture", $token, (object) []);

        $captureStatus = $body['status'] ?? 'FAILED';
        $ok = $status < 300 && $captureStatus === 'COMPLETED';

        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE donations SET status = ?, raw_response = ?,
            donor_name = ?, donor_email = ? WHERE provider = ? AND provider_order_id = ?');
        $payer = $body['payer'] ?? [];
        $name = trim(($payer['name']['given_name'] ?? '') . ' ' . ($payer['name']['surname'] ?? ''));
        $stmt->execute([
            $ok ? 'completed' : 'failed',
            substr(json_encode($body), 0, 60000),
            $name !== '' ? $name : null,
            $payer['email_address'] ?? null,
            'paypal',
            $orderId,
        ]);

        if (!$ok) {
            error_log('PayPal capture failed: ' . json_encode($body));
            Response::error('Payment could not be completed. Please try again.', 502);
        }

        ActivityLog::record('create', 'donation', null, 'PayPal donation captured: order ' . $orderId);
        Response::json(['status' => 'completed', 'orderID' => $orderId]);
    }

    /** @return array{0:string,1:string} [client_id, secret] */
    private static function credentials(): array
    {
        $clientId = SiteSettings::get('paypal_client_id');
        $secret = SiteSettings::get('paypal_secret');
        if (!$clientId || !$secret) {
            Response::error('PayPal is not configured yet. Add your PayPal sandbox Client ID and Secret in Settings → Donations.', 422);
        }
        return [$clientId, $secret];
    }

    private static function getAccessToken(string $clientId, string $secret): string
    {
        $ch = curl_init(self::apiBase() . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $clientId . ':' . $secret,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $body = json_decode((string) $raw, true) ?: [];
        if ($status >= 300 || empty($body['access_token'])) {
            error_log('PayPal OAuth failed: ' . $raw);
            Response::error('PayPal authentication failed. Check the credentials in Settings → Donations.', 502);
        }
        return $body['access_token'];
    }

    /** @return array{0:int,1:array} [http_status, decoded_body] */
    private static function request(string $method, string $path, string $token, $payload): array
    {
        $ch = curl_init(self::apiBase() . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$status, json_decode((string) $raw, true) ?: []];
    }
}
