<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use Illuminate\Support\Facades\Http;

class CashfreeGateway implements PaymentGatewayInterface
{
    public function code(): string
    {
        return 'CASHFREE';
    }

    public function credentialFields(): array
    {
        return [['name' => 'client_id', 'secret' => false], ['name' => 'client_secret', 'secret' => true]];
    }

    private function base(PaymentProvider $p): string
    {
        return $p->environment === 'live' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
    }

    private function http(PaymentProvider $p)
    {
        $c = $p->configuration;

        return Http::withHeaders(['x-client-id' => $c['client_id'], 'x-client-secret' => $c['client_secret'], 'x-api-version' => '2023-08-01']);
    }

    public function testConnection(PaymentProvider $p): array
    {
        return ['connected' => $this->http($p)->get($this->base($p).'/orders/nonexistent-connection-test')->status() !== 401];
    }

    public function initiate(PaymentProvider $p, PaymentAttempt $a, array $u): array
    {
        $r = $this->http($p)->post($this->base($p).'/orders', ['order_id' => $a->id, 'order_amount' => (float) $a->amount, 'order_currency' => $a->currency, 'customer_details' => ['customer_id' => (string) ($a->order->user_id ?? $a->order_id), 'customer_name' => $u['name'], 'customer_email' => $u['email'], 'customer_phone' => $u['mobile']]])->throw()->json();

        return ['provider_order_id' => $r['order_id'], 'action' => ['type' => 'cashfree_checkout', 'payment_session_id' => $r['payment_session_id'], 'mode' => $p->environment === 'live' ? 'production' : 'sandbox']];
    }

    public function verifyReturn(PaymentProvider $p, PaymentAttempt $a, array $v): array
    {
        return ['valid' => false, 'status' => 'pending'];
    }

    public function verifyWebhook(PaymentProvider $p, string $raw, array $h): array
    {
        $ts = $h['x-webhook-timestamp'][0] ?? '';
        $sig = $h['x-webhook-signature'][0] ?? '';
        $expected = base64_encode(hash_hmac('sha256', $ts.$raw, $p->configuration['client_secret'], true));

        return ['valid' => hash_equals($expected, $sig), 'payload' => json_decode($raw, true)];
    }
}
