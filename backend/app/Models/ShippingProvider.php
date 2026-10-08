<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingProvider extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'is_default' => 'boolean', 'last_connection_test_at' => 'datetime', 'configuration' => 'encrypted:array'];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function maskedConfiguration(): array
    {
        $c = $this->configuration ?? [];
        $out = [];
        foreach ($c as $k => $v) {
            $out[$k] = in_array($k, ['password', 'api_key', 'api_secret', 'webhook_secret'], true) ? null : $v;
        }foreach (['password', 'api_key', 'api_secret', 'webhook_secret'] as $k) {
            $out[$k.'_configured'] = ! empty($c[$k]);
        }

        return $out;
    }
}
