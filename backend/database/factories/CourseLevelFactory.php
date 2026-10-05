<?php

namespace Database\Factories;

use App\Models\CourseLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLevel>
 */
class CourseLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return ['name' => ucfirst($name), 'slug' => str($name)->slug(), 'sort_order' => fake()->numberBetween(1, 100), 'is_active' => true];
    }
}
