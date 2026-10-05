<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProviderInterface;
use App\Exceptions\ShippingProviderException;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ShiprocketShippingProvider implements ShippingProviderInterface
{
    public function code(): string
    {
        return 'SHIPROCKET';
    }

    public function credentialFields(): array
    {
        return [['key' => 'email', 'label' => 'API User Email', 'type' => 'email', 'secret' => false, 'required' => true], ['key' => 'password', 'label' => 'API Password', 'type' => 'password', 'secret' => true, 'required' => true], ['key' => 'webhook_secret', 'label' => 'Webhook x-api-key', 'type' => 'password', 'secret' => true, 'required' => false]];
    }

    private function base(ShippingProvider $p): string
    {
        return rtrim($p->configuration['base_url'] ?? 'https://apiv2.shiprocket.in/v1/external', '/');
    }

    private function token(ShippingProvider $p): string
    {
        return Cache::remember('shipping:shiprocket:'.$p->id.':auth-token', now()->addDays(9), function () use ($p) {
            $c = $p->configuration ?? [];
            $r = Http::timeout(12)->post($this->base($p).'/auth/login', ['email' => $c['email'] ?? '', 'password' => $c['password'] ?? '']);
            if (! $r->successful() || ! $r->json('token')) {
                throw new ShippingProviderException('PROVIDER_AUTH_FAILED', 'Unable to authenticate with Shiprocket.');
            }

            return $r->json('token');
        });
    }

    public function testConnection(ShippingProvider $p): array
    {
        $this->token($p);

        return ['connected' => true, 'provider' => $this->code(), 'message' => 'Connection successful.'];
    }

    private function get(ShippingProvider $p, string $path, array $query): array
    {
        $r = Http::timeout(12)->withToken($this->token($p))->get($this->base($p).$path, $query);
        if ($r->status() === 401) {
            Cache::forget('shipping:shiprocket:'.$p->id.':auth-token');
            $r = Http::timeout(12)->withToken($this->token($p))->get($this->base($p).$path, $query);
        }if (! $r->successful()) {
            throw new ShippingProviderException('PROVIDER_UNAVAILABLE', 'Shiprocket is temporarily unavailable.');
        }

        return $r->json();
    }

    private function post(ShippingProvider $p, string $path, array $payload): array
    {
        $response = Http::timeout(20)->withToken($this->token($p))->post($this->base($p).$path, $payload);
        if (! $response->successful()) {
            throw new ShippingProviderException('PROVIDER_REQUEST_FAILED', 'Shiprocket could not complete the shipment operation.');
        }

        return $response->json();
    }

    public function serviceability(ShippingProvider $p, array $q): array
    {
        $data = $this->get($p, '/courier/serviceability/', ['pickup_postcode' => $q['origin_postal_code'], 'delivery_postcode' => $q['destination_postal_code'], 'weight' => $q['weight_kg'], 'cod' => $q['cod'] ? 1 : 0, 'declared_value' => $q['declared_value']]);
        $couriers = $data['data']['available_courier_companies'] ?? [];

        return ['serviceable' => count($couriers) > 0, 'provider' => $this->code(), 'couriers' => $couriers];
    }

    public function rates(ShippingProvider $p, array $q): array
    {
        $s = $this->serviceability($p, $q);

        return array_map(fn ($x) => ['provider' => $this->code(), 'courier_code' => (string) ($x['courier_company_id'] ?? $x['courier_name'] ?? ''), 'courier_name' => $x['courier_name'] ?? 'Shiprocket Courier', 'charge' => (string) ($x['rate'] ?? 0), 'cod_charge' => (string) ($x['cod_charges'] ?? 0), 'cod_available' => (bool) ($x['cod'] ?? $q['cod']), 'estimated_delivery' => $x['etd'] ?? null], $s['couriers']);
    }

    public function createShipment(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/orders/create/adhoc', $payload);

        return ['provider_order_id' => (string) ($result['order_id'] ?? ''), 'provider_shipment_id' => (string) ($result['shipment_id'] ?? ''), 'status' => 'booked'];
    }

    public function assignAwb(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/courier/assign/awb', ['shipment_id' => $payload['provider_shipment_id'], 'courier_id' => $payload['courier_code']]);
        $data = $result['response']['data'] ?? $result;

        return ['awb_number' => (string) ($data['awb_code'] ?? ''), 'courier_name' => $data['courier_name'] ?? null];
    }

    public function schedulePickup(ShippingProvider $provider, array $payload): array
    {
        return $this->post($provider, '/courier/generate/pickup', ['shipment_id' => [$payload['provider_shipment_id']]]);
    }

    public function generateLabel(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/courier/generate/label', ['shipment_id' => [$payload['provider_shipment_id']]]);

        return ['url' => $result['label_url'] ?? null];
    }

    public function generateManifest(ShippingProvider $provider, array $payload): array
    {
        $result = $this->post($provider, '/manifests/generate', ['shipment_id' => [$payload['provider_shipment_id']]]);

        return ['url' => $result['manifest_url'] ?? null];
    }

    public function cancelShipment(ShippingProvider $provider, string $id): array
    {
        return $this->post($provider, '/orders/cancel', ['ids' => [$id]]);
    }

    public function trackShipment(ShippingProvider $provider, string $id): array
    {
        return $this->get($provider, '/courier/track/awb/'.$id, []);
    }
}
