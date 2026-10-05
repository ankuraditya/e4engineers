<?php

namespace Database\Factories;

use App\Models\ShippingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShippingProviderFactory extends Factory
{
    protected $model = ShippingProvider::class;

    public function definition(): array
    {
        $code = 'TEST'.Str::upper(Str::random(8));

        return ['code' => $code, 'name' => $code, 'is_enabled' => false, 'is_default' => false, 'priority' => 100, 'connection_status' => 'not_tested', 'configuration' => []];
    }
}
