<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Book;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\DigitalResource;
use App\Models\Publication;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LegacyWordPressCourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LiveContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_contains_only_verified_legacy_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Article::count());
        $this->assertSame(1, Course::count());
        $this->assertSame(0, Book::count());
        $this->assertSame(0, Publication::count());
        $this->assertSame(0, DigitalResource::count());
        $this->assertSame(0, Contributor::count());

        $course = Course::firstOrFail();
        $this->assertSame('fundamentals-of-electrical-and-electronics-engineering', $course->slug);
        $this->assertStringContainsString('electric charge', $course->description);
        $this->assertCount(0, $course->modules);

        $this->getJson('/api/v1/articles?per_page=20')->assertOk()->assertJsonCount(7, 'data');
        $this->getJson('/api/v1/courses?per_page=20')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/books')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/publications')->assertOk()->assertJsonCount(0, 'data');

        $course->update(['description' => '<p>Client-approved update</p>']);
        $this->seed(LegacyWordPressCourseSeeder::class);
        $this->assertSame('<p>Client-approved update</p>', $course->fresh()->description);
    }
}
