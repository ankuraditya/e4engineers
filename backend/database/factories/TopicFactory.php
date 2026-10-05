<?php

namespace Database\Factories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => ucfirst($name), 'slug' => str($name)->slug(), 'sort_order' => fake()->numberBetween(1, 100), 'is_active' => true];
    }
}
