<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentWebhookEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }
}
