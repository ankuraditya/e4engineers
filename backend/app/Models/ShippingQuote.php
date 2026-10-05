<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ShippingQuote extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['charge' => 'decimal:2', 'cod_charge' => 'decimal:2', 'cod_available' => 'boolean', 'quoted_at' => 'datetime', 'expires_at' => 'datetime', 'metadata' => 'array'];
    }
}
