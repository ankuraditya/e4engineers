<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['RAZORPAY', 'Razorpay', 'online', 'test'], ['PAYU', 'PayU', 'online', 'test'], ['CASHFREE', 'Cashfree', 'online', 'sandbox'], ['COD', 'Cash on Delivery', 'offline', 'test']] as [$code,$name,$type,$env]) {
            PaymentProvider::updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'environment' => $env, 'is_enabled' => $code === 'COD', 'sort_order' => $code === 'COD' ? 10 : 100]);
        }
        PaymentProvider::firstOrCreate(['code' => 'SCANPAY'], ['name' => 'Scan & Pay', 'type' => 'manual', 'environment' => 'offline', 'is_enabled' => false, 'sort_order' => 20]);
        PaymentSetting::current();
    }
}
