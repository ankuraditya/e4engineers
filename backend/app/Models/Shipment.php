<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => ShipmentStatus::class, 'pickup_location_snapshot' => 'array', 'estimated_delivery_date' => 'date', 'shipped_at' => 'datetime', 'picked_up_at' => 'datetime', 'delivered_at' => 'datetime', 'cancelled_at' => 'datetime', 'failed_at' => 'datetime', 'last_tracked_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function provider()
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    public function attempts()
    {
        return $this->hasMany(ShipmentAttempt::class);
    }

    public function trackingEvents()
    {
        return $this->hasMany(ShipmentTrackingEvent::class)->orderBy('occurred_at');
    }
}
