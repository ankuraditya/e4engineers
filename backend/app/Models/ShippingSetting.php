<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['shipping_enabled' => 'boolean', 'automatic_fallback' => 'boolean', 'fallback_to_flat_rate' => 'boolean', 'free_shipping_enabled' => 'boolean', 'flat_shipping_enabled' => 'boolean', 'provider_live_rates_enabled' => 'boolean', 'show_delivery_estimate' => 'boolean', 'automatic_shipment_creation' => 'boolean', 'automatic_awb_assignment' => 'boolean', 'automatic_pickup_scheduling' => 'boolean', 'automatic_label_generation' => 'boolean', 'automatic_manifest_generation' => 'boolean', 'free_shipping_threshold' => 'decimal:2', 'flat_shipping_charge' => 'decimal:2', 'rate_markup_percentage' => 'decimal:2'];
    }

    public static function current(): self
    {
        $settings = static::firstOrCreate([], []);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }

    public function defaultProvider()
    {
        return $this->belongsTo(ShippingProvider::class, 'default_provider_id');
    }

    public function fallbackProvider()
    {
        return $this->belongsTo(ShippingProvider::class, 'fallback_provider_id');
    }
}
