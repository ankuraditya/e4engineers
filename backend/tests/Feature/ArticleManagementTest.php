<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\EngineeringDiscipline;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function article(array $data = []): Article
    {
        return Article::create($data + ['title' => 'Grid Economics', 'slug' => 'grid-economics', 'content' => '<h2 id="intro">Intro</h2><p>Safe</p>', 'engineering_discipline_id' => EngineeringDiscipline::where('slug', 'electrical-engineering')->value('id'), 'status' => 'published', 'published_at' => now(), 'reading_time_minutes' => 1]);
    }

    public function test_public_api_only_returns_published_articles_and_supports_filters(): void
    {
        $published = $this->article(['is_featured' => true]);
        $this->article(['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft', 'published_at' => null]);
        $this->getJson('/api/v1/articles?featured=1&discipline=electrical-engineering')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $published->id);
        $this->getJson('/api/v1/articles/draft')->assertNotFound();
    }

    public function test_content_manager_creates_sanitized_article_with_stable_slug_and_reading_time(): void
    {
        $d = EngineeringDiscipline::where('slug', 'electrical-engineering')->firstOrFail();
        $created = $this->actingAs($this->user('content-manager'), 'web')->postJson('/api/v1/admin/articles', ['title' => 'Safe Power Article', 'content' => '<h2>Introduction</h2><p onclick="bad()">Words</p><script>alert(1)</script>', 'engineering_discipline_id' => $d->id, 'status' => 'published'])->assertCreated()->assertJsonPath('data.slug', 'safe-power-article')->json('data');
        $stored = Article::findOrFail($created['id']);
        $this->assertStringNotContainsString('script', $stored->content);
        $this->assertStringNotContainsString('onclick', $stored->content);
        $this->assertStringContainsString('id="introduction"', $stored->content);
        $this->assertGreaterThanOrEqual(1, $stored->reading_time_minutes);
        $this->patchJson("/api/v1/admin/articles/{$stored->id}", ['title' => 'Renamed'])->assertOk()->assertJsonPath('data.slug', 'safe-power-article');
    }

    public function test_detail_contains_toc_related_previous_next_and_seo(): void
    {
        $old = $this->article(['title' => 'Old', 'slug' => 'old', 'published_at' => now()->subDay()]);
        $current = $this->article(['title' => 'Current', 'slug' => 'current', 'published_at' => now()->subHour()]);
        $new = $this->article(['title' => 'New', 'slug' => 'new', 'published_at' => now()]);
        $current->seo()->create(['meta_title' => 'Current SEO']);
        $this->getJson('/api/v1/articles/current')->assertOk()->assertJsonPath('data.article.seo.meta_title', 'Current SEO')->assertJsonPath('data.article.table_of_contents.0.id', 'intro')->assertJsonPath('data.previous.slug', 'old')->assertJsonPath('data.next.slug', 'new')->assertJsonCount(2, 'data.related');
    }

    public function test_permissions_are_conservative(): void
    {
        $article = $this->article();
        $this->actingAs($this->user('course-manager'), 'web')->getJson('/api/v1/admin/articles')->assertOk();
        $this->patchJson("/api/v1/admin/articles/{$article->id}", ['title' => 'Denied'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->user('customer'), 'web')->getJson('/api/v1/admin/articles')->assertForbidden();
    }
}
