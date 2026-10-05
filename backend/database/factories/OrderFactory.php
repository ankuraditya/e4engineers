<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return ['order_number' => 'E4E-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'user_id' => User::factory(), 'status' => OrderStatus::Confirmed, 'payment_status' => PaymentStatus::CodPending, 'shipping_status' => ShippingStatus::NotCreated, 'payment_method' => 'cod', 'currency' => 'INR', 'subtotal' => 499, 'discount_total' => 0, 'shipping_total' => 80, 'cod_charge' => 0, 'tax_total' => 0, 'grand_total' => 579, 'idempotency_key' => (string) Str::uuid(), 'guest_access_token_hash' => hash('sha256', Str::random(64)), 'placed_at' => now()];
    }
}
