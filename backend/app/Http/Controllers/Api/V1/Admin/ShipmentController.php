<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipping\ShipmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShipmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $items = Shipment::with(['order:id,order_number', 'provider:id,code,name'])->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->latest()->paginate(25);

        return $this->successResponse($items->items(), meta: ['pagination' => ['total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        return $this->successResponse($shipment->load(['order.items', 'provider', 'attempts', 'trackingEvents']));
    }

    public function create(Request $request, Order $order, ShipmentService $service): JsonResponse
    {
        $data = $request->validate(['provider' => 'nullable|in:NIMBUSPOST,SHIPROCKET', 'courier_code' => 'nullable|string|max:100', 'pickup_location_id' => 'nullable|exists:shipping_pickup_locations,id']);

        return $this->successResponse($service->create($order, $data['provider'] ?? null, $data['courier_code'] ?? null, $data['pickup_location_id'] ?? null), status: 201);
    }

    public function awb(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->assignAwb($shipment->load('provider')));
    }

    public function courier(Request $request, Shipment $shipment, ShipmentService $service): JsonResponse
    {
        $data = $request->validate(['courier_code' => 'required|string|max:100', 'courier_name' => 'nullable|string|max:150']);

        return $this->successResponse($service->changeCourier($shipment, $data['courier_code'], $data['courier_name'] ?? null));
    }

    public function pickup(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->pickup($shipment->load('provider')));
    }

    public function label(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->document($shipment->load('provider'), 'label'));
    }

    public function manifest(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->document($shipment->load('provider'), 'manifest'));
    }

    public function refresh(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->refreshTracking($shipment->load('provider')));
    }

    public function reconcile(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->refresh($shipment, $service);
    }

    public function retry(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->retry($shipment->load(['provider', 'order.shippingAddress'])));
    }

    public function cancel(Shipment $shipment, ShipmentService $service): JsonResponse
    {
        return $this->successResponse($service->cancel($shipment->load('provider')));
    }

    public function download(Shipment $shipment, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['label', 'manifest'], true), 404);
        $path = $shipment->{$type.'_path'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $type.'-'.$shipment->id.'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
