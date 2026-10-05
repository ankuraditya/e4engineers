<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use Illuminate\Support\Facades\Http;

class RazorpayGateway implements PaymentGatewayInterface
{
    public function code(): string
    {
        return 'RAZORPAY';
    }

    public function credentialFields(): array
    {
        return [['name' => 'key_id', 'secret' => false], ['name' => 'key_secret', 'secret' => true], ['name' => 'webhook_secret', 'secret' => true]];
    }

    private function c(PaymentProvider $p): array
    {
        return $p->configuration ?? [];
    }

    public function testConnection(PaymentProvider $p): array
    {
        $c = $this->c($p);
        $r = Http::withBasicAuth($c['key_id'], $c['key_secret'])->get('https://api.razorpay.com/v1/orders', ['count' => 1]);

        return ['connected' => $r->successful()];
    }

    public function initiate(PaymentProvider $p, PaymentAttempt $a, array $customer): array
    {
        $c = $this->c($p);
        $r = Http::withBasicAuth($c['key_id'], $c['key_secret'])->post('https://api.razorpay.com/v1/orders', ['amount' => (int) round($a->amount * 100), 'currency' => $a->currency, 'receipt' => $a->order->order_number])->throw()->json();

        return ['provider_order_id' => $r['id'], 'action' => ['type' => 'razorpay_checkout', 'key_id' => $c['key_id'], 'order_id' => $r['id'], 'amount' => $r['amount'], 'currency' => $r['currency']]];
    }

    public function verifyReturn(PaymentProvider $p, PaymentAttempt $a, array $v): array
    {
        $expected = hash_hmac('sha256', $a->provider_order_id.'|'.$v['razorpay_payment_id'], $this->c($p)['key_secret']);

        return ['valid' => hash_equals($expected, $v['razorpay_signature']), 'payment_id' => $v['razorpay_payment_id'], 'status' => 'success'];
    }

    public function verifyWebhook(PaymentProvider $p, string $raw, array $h): array
    {
        $sig = $h['x-razorpay-signature'][0] ?? '';

        return ['valid' => hash_equals(hash_hmac('sha256', $raw, $this->c($p)['webhook_secret']), $sig), 'payload' => json_decode($raw,true)];
    }
}
