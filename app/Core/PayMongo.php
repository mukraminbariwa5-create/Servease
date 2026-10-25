<?php
declare(strict_types=1);

/**
 * PayMongo hosted checkout (GCash, Maya, cards, QR Ph).
 * Only used when payments.paymongo.enabled = true in config.
 * Docs: https://docs.paymongo.com/reference/create-a-checkout
 */
final class PayMongo
{
    private const API = 'https://api.paymongo.com/v1';

    /** @return array{id:string, checkout_url:string} */
    public static function createCheckout(array $booking, string $successUrl, string $cancelUrl): array
    {
        $amountCentavos = (int) round(((float) $booking['amount']) * 100);
        $payload = ['data' => ['attributes' => [
            'line_items' => [[
                'currency' => 'PHP',
                'amount'   => $amountCentavos,
                'name'     => mb_substr($booking['title'], 0, 120),
                'quantity' => 1,
            ]],
            'payment_method_types' => array_values(config('payments.paymongo.methods', ['gcash', 'paymaya', 'card'])),
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'description'          => 'Servease booking ' . booking_ref((int) $booking['id']),
            'reference_number'     => booking_ref((int) $booking['id']),
            'send_email_receipt'   => false,
            'show_description'     => true,
            'show_line_items'      => true,
            'metadata'             => ['booking_id' => (string) $booking['id']],
        ]]];

        $res = self::request('POST', '/checkout_sessions', $payload);
        $id = $res['data']['id'] ?? null;
        $url = $res['data']['attributes']['checkout_url'] ?? null;
        if (!$id || !$url) throw new RuntimeException('PayMongo did not return a checkout URL.');
        return ['id' => $id, 'checkout_url' => $url];
    }

    /** Returns true when the checkout session has a successful payment. */
    public static function isPaid(string $checkoutId): bool
    {
        $res = self::request('GET', '/checkout_sessions/' . rawurlencode($checkoutId));
        $attr = $res['data']['attributes'] ?? [];
        foreach ($attr['payments'] ?? [] as $p) {
            if (($p['attributes']['status'] ?? '') === 'paid') return true;
        }
        return ($attr['payment_intent']['attributes']['status'] ?? '') === 'succeeded';
    }

    /** Verify the Paymongo-Signature header: "t=<ts>,te=<test sig>,li=<live sig>" */
    public static function verifyWebhook(string $rawBody, string $header): bool
    {
        $secret = (string) config('payments.paymongo.webhook_secret', '');
        if ($secret === '' || $header === '') return false;
        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, '');
            $parts[$k] = $v;
        }
        if (empty($parts['t'])) return false;
        $expected = hash_hmac('sha256', $parts['t'] . '.' . $rawBody, $secret);
        foreach (['te', 'li'] as $k) {
            if (!empty($parts[$k]) && hash_equals($expected, $parts[$k])) return true;
        }
        return false;
    }

    private static function request(string $method, string $path, ?array $body = null): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('The PHP cURL extension is required for PayMongo.');
        $ch = curl_init(self::API . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(config('payments.paymongo.secret_key') . ':'),
            ],
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('Could not reach PayMongo: ' . $err);
        $json = json_decode((string) $raw, true) ?: [];
        if ($status >= 400) {
            $detail = $json['errors'][0]['detail'] ?? "HTTP $status";
            throw new RuntimeException('PayMongo error: ' . $detail);
        }
        return $json;
    }
}
