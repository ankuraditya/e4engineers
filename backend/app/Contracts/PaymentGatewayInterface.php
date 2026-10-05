<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;

interface PaymentGatewayInterface
{
    public function code(): string;

    public function credentialFields(): array;

    public function testConnection(PaymentProvider $provider): array;

    public function initiate(PaymentProvider $provider, PaymentAttempt $attempt, array $customer): array;

    public function verifyReturn(PaymentProvider $provider, PaymentAttempt $attempt, array $payload): array;

    public function verifyWebhook(PaymentProvider $provider, string $raw, array $headers): array;
}
