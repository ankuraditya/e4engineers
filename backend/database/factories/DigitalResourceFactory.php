<?php

namespace Database\Factories;

use App\Models\DigitalResource;
use App\Models\EngineeringDiscipline;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalResourceFactory extends Factory
{
    protected $model = DigitalResource::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return ['title' => $title, 'slug' => str($title)->slug(), 'short_description' => fake()->sentence(), 'description' => '<p>'.fake()->paragraph().'</p>', 'resource_type_id' => ResourceType::factory(), 'engineering_discipline_id' => EngineeringDiscipline::factory(), 'access_type' => 'free', 'status' => 'draft', 'preview_type' => 'none'];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function published(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()]);
    }

    public function featured(): static
    {
        return $this->published()->state(['is_featured' => true, 'featured_order' => 1]);
    }

    public function free(): static
    {
        return $this->state(['access_type' => 'free', 'price' => null]);
    }

    public function loginRequired(): static
    {
        return $this->state(['access_type' => 'login_required', 'price' => null]);
    }

    public function paid(): static
    {
        return $this->state(['access_type' => 'paid', 'price' => 499]);
    }
}
