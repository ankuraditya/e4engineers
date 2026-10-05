<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponDemoSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(['code' => 'E4SAVE10'], ['name' => 'E4ENGINEERS 10% Launch Offer', 'description' => 'Save 10% on eligible engineering books.', 'discount_type' => 'percentage', 'discount_value' => 10, 'minimum_subtotal' => 500, 'maximum_discount' => 500, 'is_active' => true, 'applies_to' => 'all_books']);
    }
}
