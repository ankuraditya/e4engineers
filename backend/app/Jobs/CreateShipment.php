<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ShippingSetting;
use App\Services\Shipping\ShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateShipment implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $orderId) {}

    public function handle(ShipmentService $service): void
    {
        $settings = ShippingSetting::current();
        if (! $settings->automatic_shipment_creation) {
            return;
        }
        $order = Order::findOrFail($this->orderId);
        $provider = $settings->defaultProvider?->code;
        $shipment = $service->create($order, $provider, pickupId: $settings->default_pickup_location_id);
        if ($settings->automatic_awb_assignment && ! $shipment->awb_number) {
            $shipment = $service->assignAwb($shipment->load('provider'));
        }
        if ($settings->automatic_pickup_scheduling && $shipment->awb_number) {
            $shipment = $service->pickup($shipment->load('provider'));
        }
        if ($settings->automatic_label_generation && $shipment->awb_number) {
            $service->document($shipment->load('provider'), 'label');
        }
        if ($settings->automatic_manifest_generation && $shipment->awb_number) {
            $service->document($shipment->load('provider'), 'manifest');
        }
    }
}
