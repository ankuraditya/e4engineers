<?php

namespace App\Events;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public Shipment $shipment, public ShipmentStatus $from, public ShipmentStatus $to) {}
}
