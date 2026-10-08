<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProviderInterface;
use App\Exceptions\ShippingProviderException;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NimbusPostShippingProvider implements ShippingProviderInterface
{
    public function code(): string
    {
        return 'NIMBUSPOST';
    }

    public function credentialFields(): array
    {
        return [['key' => 'email', 'label' => 'API User Email', 'type' => 'email', 'secret' => false, 'required' => true], ['key' => 'password', 'label' => 'API Password', 'type' => 'password', 'secret' => true, 'required' => true], ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'secret' => true, 'required' => false], ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'type' => 'password', 'secret' => true, 'required' => false]];
    }

    private function base(ShippingProvider $p): string
    {
        return rtrim($p->configuration['base_url'] ?? 'https://api.nimbuspost.com/v1', '/');
    }

    private function token(ShippingProvider $p): string
    {
        return Cache::remember('shipping:nimbuspost:'.$p->id.':auth-token', now()->addHours(20), function () use ($p) {
            $c = $p->configuration ?? [];
            $r = Http::timeout(12)->post($this->base($p).'/users/login', ['email' => $c['email'] ?? '', 'password' => $c['password'] ?? '']);
            $token = $r->json('data.token') ?? $r->json('token');
            if (! $r->successful() || ! $token) {
                throw new ShippingProviderException('PROVIDER_AUTH_FAILED', 'NimbusPost rejected the API user credentials. Generate API User Email and Password in NimbusPost Settings → API.');
            }

            return $token;
        });
    }

    public function testConnection(ShippingProvider $p): array
    {
        $this->token($p);

        return ['connected' => true, 'provider' => $this->code(), 'message' => 'Connection successful.'];
    }

    public function serviceability(ShippingProvider $p, array $q): array
    {
        $r = Http::timeout(12)->withToken($this->token($p))->post($this->base($p).'/courier/serviceability', ['origin' => $q['origin_postal_code'], 'destination' => $q['destination_postal_code'], 'weight' => $q['weight_grams'], 'payment_type' => $q['cod'] ? 'cod' : 'prepaid', 'order_amount' => $q['declared_value']]);
        if (! $r->successful()) {
            throw new ShippingProviderException('PROVIDER_UNAVAILABLE', 'NimbusPost is temporarily unavailable.');
        }$couriers = $r->json('data') ?? [];

        return ['serviceable' => count($couriers) > 0, 'provider' => $this->code(), 'couriers' => $couriers];
    }

    public function rates(ShippingProvider $p, array $q): array
    {
        $s = $this->serviceability($p, $q);

        return array_map(fn ($x) => ['provider' => $this->code(), 'courier_code' => (string) ($x['courier_id'] ?? $x['id'] ?? ''), 'courier_name' => $x['courier_name'] ?? $x['name'] ?? 'NimbusPost Courier', 'charge' => (string) ($x['freight_charge'] ?? $x['rate'] ?? 0), 'cod_charge' => (string) ($x['cod_charges'] ?? 0), 'cod_available' => (bool) ($x['cod'] ?? $q['cod']), 'estimated_delivery' => $x['estimated_delivery'] ?? $x['etd'] ?? null], $s['couriers']);
    }

    private function post(ShippingProvider $provider, string $path, array $payload): array
    {
        $response = Http::timeout(20)->withToken($this->token($provider))->post($this->base($provider).$path, $payload);
        if (! $response->successful()) {
            throw new ShippingProviderException('PROVIDER_REQUEST_FAILED', 'NimbusPost could not complete the shipment operation.');
        }

        return $response->json();
    }

    public function createShipment(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/shipments', $payload);
        $data = $result['data'] ?? $result;

        return ['provider_order_id' => (string) ($data['order_id'] ?? $payload['order_number']), 'provider_shipment_id' => (string) ($data['shipment_id'] ?? ''), 'awb_number' => $data['awb_number'] ?? $data['awb'] ?? null, 'courier_name' => $data['courier_name'] ?? null, 'status' => 'booked'];
    }

    public function assignAwb(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/shipments/awb', ['shipment_id' => $payload['provider_shipment_id'], 'courier_id' => $payload['courier_code']]);
        $data = $result['data'] ?? $result;

        return ['awb_number' => (string) ($data['awb_number'] ?? $data['awb'] ?? ''), 'courier_name' => $data['courier_name'] ?? null];
    }

    public function schedulePickup(ShippingProvider $provider, array $payload): array
    {
        return $this->post($provider, '/shipments/pickup', ['shipment_id' => $payload['provider_shipment_id']]);
    }

    public function generateLabel(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/shipments/label', ['shipment_id' => $payload['provider_shipment_id']]);

        return ['url' => data_get($result, 'data.url') ?? $result['url'] ?? null];
    }

    public function generateManifest(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/shipments/manifest', ['shipment_id' => $payload['provider_shipment_id']]);

        return ['url' => data_get($result, 'data.url') ?? $result['url'] ?? null];
    }

    public function cancelShipment(ShippingProvider $provider, string $id): array
    {
        return $this->post($provider, '/shipments/cancel', ['shipment_id' => $id]);
    }

    public function trackShipment(ShippingProvider $provider, string $id): array
    {
        $response = Http::timeout(20)->withToken($this->token($provider))->get($this->base($provider).'/shipments/track/'.$id);
        if (! $response->successful()) {
            throw new ShippingProviderException('TRACKING_FAILED', 'NimbusPost tracking is temporarily unavailable.');
        }

        return $response->json();
    }
}
