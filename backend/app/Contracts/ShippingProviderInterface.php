<?php

namespace App\Contracts;

use App\Models\ShippingProvider;

interface ShippingProviderInterface
{
    public function code(): string;

    public function credentialFields(): array;

    public function testConnection(ShippingProvider $provider): array;

    public function serviceability(ShippingProvider $provider, array $request): array;

    public function rates(ShippingProvider $provider, array $request): array;

    public function createShipment(ShippingProvider $provider, array $payload): array;

    public function assignAwb(ShippingProvider $provider, array $payload): array;

    public function schedulePickup(ShippingProvider $provider, array $payload): array;

    public function generateLabel(ShippingProvider $provider, array $payload): array;

    public function generateManifest(ShippingProvider $provider, array $payload): array;

    public function cancelShipment(ShippingProvider $provider, string $shipmentId): array;

    public function trackShipment(ShippingProvider $provider, string $reference): array;
}
