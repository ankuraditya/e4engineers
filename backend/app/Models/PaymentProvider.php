<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    protected $guarded = [];

    protected $hidden = ['configuration'];

    protected function casts(): array
    {
        return ['configuration' => 'encrypted:array', 'is_enabled' => 'boolean', 'is_default' => 'boolean', 'last_connection_test_at' => 'datetime'];
    }

    public function publicConfiguration(): array
    {
        $c = $this->configuration ?? [];
        $secrets = ['key_secret', 'webhook_secret', 'merchant_salt', 'client_secret'];
        $out = [];
        foreach ($c as $k => $v) {
            $out[in_array($k, $secrets, true) ? $k.'_configured' : $k] = in_array($k, $secrets, true) ? filled($v) : $v;
        }

return $out;
    }
}
