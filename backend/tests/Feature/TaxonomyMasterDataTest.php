<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EngineeringDiscipline;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxonomyMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class]);
    }

    public function test_content_manager_can_create_and_update_filtered_categories_and_tags(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('content-manager');

        $category = $this->actingAs($manager, 'web')->postJson('/api/v1/admin/categories', [
            'name' => 'Power Systems', 'context' => 'article', 'sort_order' => 1,
        ])->assertCreated()->json('data');
        $this->patchJson("/api/v1/admin/categories/{$category['id']}", ['name' => 'Power Engineering'])->assertOk();
        $this->getJson('/api/v1/categories?type=article')->assertJsonFragment(['slug' => 'power-systems']);
        $this->getJson('/api/v1/categories?type=book')->assertJsonMissing(['slug' => 'power-systems']);

        $this->postJson('/api/v1/admin/tags', ['name' => 'Smart Grid'])->assertCreated();
        $this->postJson('/api/v1/admin/tags', ['name' => 'Duplicate', 'slug' => 'smart-grid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['slug']);
    }

    public function test_topic_accepts_valid_discipline_and_public_filter_uses_discipline_slug(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('content-manager');
        $discipline = EngineeringDiscipline::query()->where('slug', 'electrical-engineering')->firstOrFail();

        $this->actingAs($manager, 'web')->postJson('/api/v1/admin/topics', [
            'name' => 'Power Generation', 'engineering_discipline_id' => $discipline->id, 'sort_order' => 1,
        ])->assertCreated();

        $this->getJson('/api/v1/topics?discipline=electrical-engineering')
            ->assertOk()->assertJsonFragment(['slug' => 'power-generation']);
        $this->getJson('/api/v1/topics?discipline=civil-engineering')
            ->assertJsonMissing(['slug' => 'power-generation']);
    }

    public function test_invalid_discipline_is_rejected_and_inactive_topic_is_hidden(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('content-manager');

        $this->actingAs($manager, 'web')->postJson('/api/v1/admin/topics', [
            'name' => 'Invalid Topic', 'engineering_discipline_id' => 999999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['engineering_discipline_id']);

        $topic = Topic::factory()->create(['is_active' => false]);
        $this->getJson('/api/v1/topics')->assertJsonMissing(['id' => $topic->id]);
    }

    public function test_unprivileged_admin_cannot_manage_taxonomy(): void
    {
        $support = User::factory()->create();
        $support->assignRole('support-manager');

        $this->actingAs($support, 'web')->postJson('/api/v1/admin/categories', [
            'name' => 'Forbidden', 'context' => 'general',
        ])->assertForbidden();

        $this->assertSame(0, Category::query()->count());

    }
}
