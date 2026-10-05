<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'type' => 'home', 'full_name' => fake()->name(), 'mobile' => '9876543210', 'address_line_1' => '123 Example Road', 'city' => 'New Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country_code' => 'IN', 'is_default' => false];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function home(): static
    {
        return $this->state(['type' => 'home']);
    }

    public function office(): static
    {
        return $this->state(['type' => 'office']);
    }
}
