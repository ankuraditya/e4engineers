<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;

class ShipmentTrackingEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => ShipmentStatus::class, 'occurred_at' => 'datetime'];
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
