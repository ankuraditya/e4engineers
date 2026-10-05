<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['online_payments_enabled' => 'boolean', 'cod_enabled' => 'boolean', 'cod_charge' => 'decimal:2'];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], ['online_payments_enabled' => false, 'cod_enabled' => true, 'cod_charge' => 0, 'attempt_expiry_minutes' => 20]);
    }
}
