<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('SAVE##??')), 'name' => fake()->words(3, true), 'discount_type' => 'percentage', 'discount_value' => 10, 'is_active' => true, 'applies_to' => 'all_books'];
    }
}
