<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'shipping_provider_id' => ShippingProvider::factory(),
            'status' => ShipmentStatus::Booked,
            'payment_mode' => 'PREPAID',
            'weight_grams' => 500,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 5,
            'pickup_location_snapshot' => ['name' => 'Main Warehouse'],
        ];
    }
}
