<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPickupLocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean', 'provider_metadata' => 'encrypted:array'];
    }
}
