<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return ['title' => $title, 'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1000, 9999), 'sku' => 'E4E-TEST-'.fake()->unique()->numberBetween(10000, 99999), 'description' => 'Engineering book test fixture.', 'format' => 'paperback', 'mrp' => 599, 'selling_price' => 499, 'currency' => 'INR', 'status' => 'published', 'published_at' => now()];
    }
}
