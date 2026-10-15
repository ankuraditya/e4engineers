<?php

namespace Database\Seeders;

use App\Models\ShippingProvider;
use App\Models\ShippingSetting;
use Illuminate\Database\Seeder;

class ShippingProviderSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['code' => 'NIMBUSPOST', 'name' => 'NimbusPost', 'priority' => 10], ['code' => 'SHIPROCKET', 'name' => 'Shiprocket', 'priority' => 20], ['code' => 'SHIPPRIME', 'name' => 'ShipPrime', 'priority' => 30]] as $provider) {
            ShippingProvider::firstOrCreate(['code' => $provider['code']], $provider + ['is_enabled' => false, 'is_default' => false, 'connection_status' => 'not_configured']);
        }ShippingSetting::current();
    }
}
