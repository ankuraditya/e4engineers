<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    use ApiResponse;

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $token = (string) $request->header('X-Order-Access-Token');
        abort_unless(($request->user() && $order->user_id === $request->user()->id) || ($token !== '' && hash_equals((string) $order->guest_access_token_hash, hash('sha256', $token))), 404);
        $shipment = $order->shipment()->with(['provider:id,name', 'trackingEvents'])->first();
        if (! $shipment) {
            return $this->successResponse(['order_number' => $order->order_number, 'status' => 'not_created', 'events' => []]);
        }

        return $this->successResponse(['order_number' => $order->order_number, 'status' => $shipment->status->value, 'courier' => ['name' => $shipment->courier_name ?: $shipment->provider->name], 'tracking_number' => $shipment->awb_number, 'estimated_delivery_date' => $shipment->estimated_delivery_date, 'last_updated_at' => $shipment->last_tracked_at ?: $shipment->updated_at, 'events' => $shipment->trackingEvents->map(fn ($event) => ['status' => $event->status->value, 'label' => $event->label, 'location' => $event->location, 'occurred_at' => $event->occurred_at])]);
    }
}
