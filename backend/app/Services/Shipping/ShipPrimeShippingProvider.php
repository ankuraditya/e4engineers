<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProviderInterface;
use App\Exceptions\ShippingProviderException;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Http;

class ShipPrimeShippingProvider implements ShippingProviderInterface
{
    private const BASE_URL = 'https://api.shipprime.live';

    public function code(): string
    {
        return 'SHIPPRIME';
    }

    public function credentialFields(): array
    {
        return [
            ['key' => 'api_token', 'label' => 'API Token', 'type' => 'password', 'secret' => true, 'required' => true],
            ['key' => 'webhook_secret', 'label' => 'Webhook Bearer Token', 'type' => 'password', 'secret' => true, 'required' => false],
        ];
    }

    private function request(ShippingProvider $provider, string $method, string $path, array $data = []): array
    {
        $token = (string) data_get($provider->configuration, 'api_token');
        if ($token === '') {
            throw new ShippingProviderException('PROVIDER_NOT_CONFIGURED', 'Add the ShipPrime API token before testing the connection.');
        }

        $client = Http::acceptJson()->withToken($token)->timeout(20);
        $response = match (strtoupper($method)) {
            'GET' => $client->get(self::BASE_URL.$path, $data),
            'POST' => $client->post(self::BASE_URL.$path, $data),
        };

        if ($response->status() === 401 || $response->status() === 403) {
            throw new ShippingProviderException('PROVIDER_AUTH_FAILED', 'ShipPrime rejected the API token.');
        }
        if (! $response->successful() || $response->json('status') !== 'SUCCESS') {
            throw new ShippingProviderException('PROVIDER_REQUEST_FAILED', 'ShipPrime could not complete the request. Check its dashboard for details.');
        }

        return $response->json();
    }

    public function testConnection(ShippingProvider $provider): array
    {
        $this->request($provider, 'GET', '/v1/forward/wallet');

        return ['connected' => true, 'provider' => $this->code(), 'message' => 'ShipPrime connection successful.'];
    }

    public function serviceability(ShippingProvider $provider, array $request): array
    {
        $rates = $this->rates($provider, $request);

        return ['serviceable' => $rates !== [], 'provider' => $this->code(), 'couriers' => $rates];
    }

    public function rates(ShippingProvider $provider, array $request): array
    {
        $result = $this->request($provider, 'GET', '/v1/forward/rates', [
            'pickupPincode' => $request['origin_postal_code'],
            'deliveryPincode' => $request['destination_postal_code'],
            'weightGrams' => $request['weight_grams'],
            'paymentMethod' => $request['cod'] ? 'COD' : 'PREPAID',
            'collectibleAmount' => $request['cod'] ? $request['declared_value'] : 0,
        ]);

        return array_map(fn (array $rate) => [
            'provider' => $this->code(),
            'courier_code' => (string) $rate['courier'],
            'courier_name' => (string) $rate['courier'],
            'charge' => (string) ($rate['forwardCharge'] ?? 0),
            'cod_charge' => (string) ($rate['codCharge'] ?? 0),
            'cod_available' => (bool) $request['cod'],
            'estimated_delivery' => null,
        ], $result['rates'] ?? []);
    }

    public function createShipment(ShippingProvider $provider, array $payload): array
    {
        $pickup = $payload['pickup_address'] ?? [];
        $items = array_map(fn (array $item) => [
            'name' => (string) $item['name'],
            'sku' => (string) ($item['sku'] ?? ''),
            'quantity' => (int) $item['units'],
            'price' => (float) $item['selling_price'],
        ], $payload['order_items']);
        $body = [
            'clientReferenceId' => (string) $payload['order_number'],
            'paymentMethod' => $payload['payment_method'],
            'collectibleAmount' => $payload['payment_method'] === 'COD' ? (float) $payload['cod_amount'] : 0,
            'weightGrams' => (int) round($payload['weight'] * 1000),
            'declaredValue' => (float) $payload['sub_total'],
            'items' => $items,
            'pickupAddress' => $this->address($pickup['contact_name'] ?? $pickup['name'] ?? '', $pickup['phone'] ?? '', $pickup['address_line1'] ?? '', $pickup['address_line2'] ?? '', $pickup['city'] ?? '', $pickup['state'] ?? '', $pickup['postal_code'] ?? ''),
            'deliveryAddress' => $this->address($payload['billing_customer_name'], $payload['billing_phone'], $payload['billing_address'], $payload['billing_address_2'] ?? '', $payload['billing_city'], $payload['billing_state'], $payload['billing_pincode']),
        ];
        if (filled($payload['courier_id'] ?? null)) {
            $body['preferredTpls'] = [(string) $payload['courier_id']];
        }

        $result = $this->request($provider, 'POST', '/v1/forward', $body);
        $awb = (string) ($result['awb'] ?? '');
        if ($awb === '') {
            throw new ShippingProviderException('BOOKING_FAILED', 'ShipPrime did not return an AWB. Reconcile the booking in ShipPrime before retrying.');
        }

        return [
            'provider_order_id' => (string) ($result['orderId'] ?? $payload['order_number']),
            'provider_shipment_id' => $awb,
            'awb_number' => $awb,
            'courier_name' => $result['courier'] ?? null,
            'label_url' => $result['labelUrl'] ?? null,
        ];
    }

    private function address(string $name, string $phone, string $line1, string $line2, string $city, string $state, string $pincode): array
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        if (! preg_match('/^[0-9]{10}$/', $digits) || ! preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
            throw new ShippingProviderException('INVALID_SHIPPING_ADDRESS', 'ShipPrime requires valid 10-digit pickup and delivery phone numbers and 6-digit PIN codes.');
        }

        return ['name' => $name, 'phone' => $digits, 'address1' => $line1, 'address2' => $line2, 'city' => $city, 'state' => $state, 'pincode' => $pincode, 'country' => 'India'];
    }

    public function assignAwb(ShippingProvider $provider, array $payload): array
    {
        throw new ShippingProviderException('UNSUPPORTED_OPERATION', 'ShipPrime assigns the AWB when the shipment is booked.');
    }

    public function schedulePickup(ShippingProvider $provider, array $payload): array
    {
        throw new ShippingProviderException('UNSUPPORTED_OPERATION', 'ShipPrime does not expose a separate pickup scheduling API. Arrange pickup in ShipPrime.');
    }

    public function generateLabel(ShippingProvider $provider, array $payload): array
    {
        $url = (string) ($payload['provider_label_url'] ?? '');
        if (! str_starts_with($url, 'https://cdn.shipprime.live/')) {
            throw new ShippingProviderException('DOCUMENT_FAILED', 'ShipPrime did not supply a valid label URL for this shipment.');
        }

        return ['url' => $url];
    }

    public function generateManifest(ShippingProvider $provider, array $payload): array
    {
        throw new ShippingProviderException('UNSUPPORTED_OPERATION', 'ShipPrime does not expose a manifest API.');
    }

    public function cancelShipment(ShippingProvider $provider, string $shipmentId): array
    {
        return $this->request($provider, 'POST', '/v1/forward/'.rawurlencode($shipmentId).'/cancel');
    }

    public function trackShipment(ShippingProvider $provider, string $reference): array
    {
        $result = $this->request($provider, 'GET', '/v1/forward/track', ['awbs' => $reference]);
        $tracking = $result['results'][0] ?? null;
        if (! $tracking || ($tracking['awb'] ?? null) !== $reference) {
            throw new ShippingProviderException('TRACKING_FAILED', 'ShipPrime has no tracking result for this AWB.');
        }

        return ['data' => ['awb_number' => $reference, 'status' => $tracking['currentStatus'] ?? 'UNKNOWN', 'events' => array_map(fn (array $event) => ['status' => $event['status'] ?? 'UNKNOWN', 'date' => $event['statusDate'] ?? now(), 'location' => $event['location'] ?? null], $tracking['history'] ?? [])]];
    }
}
