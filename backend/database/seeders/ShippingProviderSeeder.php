<?php

namespace Database\Seeders;

use App\Models\ShippingProvider;
use App\Models\ShippingSetting;
use Illuminate\Database\Seeder;

class ShippingProviderSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['code' => 'NIMBUSPOST', 'name' => 'NimbusPost', 'priority' => 10], ['code' => 'SHIPROCKET', 'name' => 'Shiprocket', 'priority' => 20]] as $provider) {
            ShippingProvider::updateOrCreate(['code' => $provider['code']], $provider + ['is_enabled' => false, 'is_default' => false, 'connection_status' => 'not_configured']);
        }ShippingSetting::current();
    }
}
