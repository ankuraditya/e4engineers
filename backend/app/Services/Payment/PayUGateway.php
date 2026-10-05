<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;

class PayUGateway implements PaymentGatewayInterface
{
    public function code(): string
    {
        return 'PAYU';
    }

    public function credentialFields(): array
    {
        return [['name' => 'merchant_key', 'secret' => false], ['name' => 'merchant_salt', 'secret' => true]];
    }

    public function testConnection(PaymentProvider $p): array
    {
        return ['connected' => filled(($p->configuration ?? [])['merchant_key'] ?? null) && filled(($p->configuration ?? [])['merchant_salt'] ?? null)];
    }

    public function initiate(PaymentProvider $p, PaymentAttempt $a, array $u): array
    {
        $c = $p->configuration;
        $tx = $a->id;
        $product = $a->order->order_number;
        $hash = hash('sha512', implode('|', [$c['merchant_key'], $tx, $a->amount, $product, $u['name'], $u['email'], '', '', '', '', '', '', '', '', '', '', $c['merchant_salt']]));

        $callback = url('/api/v1/payments/callbacks/payu');

        return ['provider_order_id' => $tx, 'action' => ['type' => 'form_post', 'url' => $p->environment === 'live' ? 'https://secure.payu.in/_payment' : 'https://test.payu.in/_payment', 'fields' => ['key' => $c['merchant_key'], 'txnid' => $tx, 'amount' => $a->amount, 'productinfo' => $product, 'firstname' => $u['name'], 'email' => $u['email'], 'phone' => $u['mobile'] ?? '', 'surl' => $callback, 'furl' => $callback, 'hash' => $hash]]];
    }

    public function verifyReturn(PaymentProvider $p, PaymentAttempt $a, array $v): array
    {
        $c = $p->configuration;
        $expected = hash('sha512', implode('|', [$c['merchant_salt'], $v['status'] ?? '', '', '', '', '', '', '', '', '', '', $v['email'] ?? '', $v['firstname'] ?? '', $v['productinfo'] ?? '', $v['amount'] ?? '', $v['txnid'] ?? '', $c['merchant_key']]));

        $amountMatches = is_numeric($v['amount'] ?? null)
            && (int) round(((float) $v['amount']) * 100) === (int) round(((float) $a->amount) * 100);

        return [
            'valid' => hash_equals($expected, $v['hash'] ?? '')
                && ($v['txnid'] ?? '') === $a->id
                && ($v['productinfo'] ?? '') === $a->order->order_number
                && $amountMatches,
            'payment_id' => $v['mihpayid'] ?? null,
            'status' => $v['status'] ?? 'pending',
        ];
    }

    public function verifyWebhook(PaymentProvider $p, string $raw, array $h): array
    {
        parse_str($raw, $payload);
        $attempt = PaymentAttempt::find($payload['txnid'] ?? '');
        if (! $attempt) {
            return ['valid' => false, 'payload' => $payload];
        }

        return ['valid' => $this->verifyReturn($p, $attempt, $payload)['valid'], 'payload' => $payload];
    }
}
