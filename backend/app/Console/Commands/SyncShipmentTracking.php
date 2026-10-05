<?php

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShippingSetting;
use App\Services\Shipping\ShipmentService;
use Illuminate\Console\Command;

class SyncShipmentTracking extends Command
{
    protected $signature = 'shipments:sync-tracking {--limit=100}';

    protected $description = 'Refresh tracking for active shipments due for synchronization';

    public function handle(ShipmentService $service): int
    {
        $minutes = ShippingSetting::current()->tracking_sync_minutes;
        $statuses = [ShipmentStatus::AwbAssigned, ShipmentStatus::PickupScheduled, ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::DeliveryFailed, ShipmentStatus::RtoInitiated, ShipmentStatus::RtoInTransit];
        Shipment::with(['provider', 'order'])->whereIn('status', $statuses)->where(fn ($query) => $query->whereNull('last_tracked_at')->orWhere('last_tracked_at', '<=', now()->subMinutes($minutes)))->limit((int) $this->option('limit'))->each(function (Shipment $shipment) use ($service): void {
            try {
                $service->refreshTracking($shipment);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });

        return self::SUCCESS;
    }
}
