<?php

namespace Database\Factories;

use App\Models\Contributor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contributor>
 */
class ContributorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'slug' => fake()->unique()->slug(3),
            'designation' => fake()->randomElement(['Professor', 'Engineer', 'Researcher', 'Technical Contributor']),
            'qualification' => fake()->randomElement(['M.Tech Engineering', 'Ph.D. Engineering', 'B.Tech Engineering']),
            'short_bio' => fake()->sentence(12),
            'biography' => '<p>'.fake()->paragraph().'</p>',
            'expertise_summary' => fake()->words(5, true),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_featured' => false,
            'is_active' => true,
            'published_at' => now(),
        ];
    }
}
