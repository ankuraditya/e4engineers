<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentWebhookEvent;
use App\Models\ShippingProvider;
use App\Services\Shipping\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, ShipmentService $service): JsonResponse
    {
        $configured = ShippingProvider::where('code', strtoupper($provider))->firstOrFail();
        $secret = (string) data_get($configured->configuration, 'webhook_secret');
        abort_unless($secret !== '' && hash_equals($secret, (string) $request->header('x-api-key')), 401);
        $raw = $request->getContent();
        $payload = $request->json()->all();
        $eventId = (string) ($request->header('x-event-id') ?: data_get($payload, 'id') ?: hash('sha256', $raw));
        $event = ShipmentWebhookEvent::firstOrCreate(['provider' => $configured->code, 'event_id' => $eventId], ['payload_hash' => hash('sha256', $raw), 'status' => 'received', 'payload' => $this->sanitize($payload)]);
        if ($event->processed_at) {
            return response()->json(['success' => true]);
        }
        $awb = (string) (data_get($payload, 'awb') ?? data_get($payload, 'awb_number') ?? data_get($payload, 'tracking_number'));
        $shipment = Shipment::where('shipping_provider_id', $configured->id)->where('awb_number', $awb)->first();
        if ($shipment) {
            $service->handleTracking($shipment, (string) (data_get($payload, 'current_status') ?? data_get($payload, 'status') ?? data_get($payload, 'shipment_status')), (string) (data_get($payload, 'status_text') ?? data_get($payload, 'current_status') ?? 'Tracking update'), data_get($payload, 'current_timestamp') ?? data_get($payload, 'event_time') ?? now(), data_get($payload, 'location'), 'webhook', $eventId);
        }
        $event->update(['status' => 'processed', 'processed_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function sanitize(array $payload): array
    {
        foreach (['token', 'api_key', 'password', 'authorization'] as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }
}
