<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\EngineeringDiscipline;
use App\Models\Media;
use App\Models\User;
use App\Services\ContributorCache;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContributorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function contributor(array $attributes = [], array $disciplineSlugs = ['electrical-engineering']): Contributor
    {
        $contributor = Contributor::factory()->create($attributes);
        foreach ($disciplineSlugs as $index => $slug) {
            $discipline = EngineeringDiscipline::query()->where('slug', $slug)->firstOrFail();
            $contributor->disciplines()->attach($discipline->id, ['is_primary' => $index === 0, 'sort_order' => $index]);
        }

        return $contributor;
    }

    public function test_public_list_filters_searches_orders_paginates_and_hides_private_data(): void
    {
        $mechanical = $this->contributor(['name' => 'Mechanical Expert', 'slug' => 'mechanical-expert', 'expertise_summary' => 'Rotating machinery', 'is_featured' => false, 'sort_order' => 1, 'email' => 'private@example.test', 'phone' => '9999999999'], ['mechanical-engineering']);
        $electrical = $this->contributor(['name' => 'Power Expert', 'slug' => 'power-expert', 'expertise_summary' => 'Grid economics', 'is_featured' => true, 'sort_order' => 2]);
        $this->contributor(['name' => 'Inactive Expert', 'slug' => 'inactive-expert', 'is_active' => false]);

        $this->getJson('/api/v1/contributors?per_page=10')->assertOk()->assertJsonPath('data.0.id', $electrical->id)->assertJsonMissing(['slug' => 'inactive-expert'])->assertJsonMissing(['email' => 'private@example.test'])->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/contributors?discipline=mechanical-engineering')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mechanical->id);
        $this->getJson('/api/v1/contributors?search=Grid')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $electrical->id);
        $this->getJson('/api/v1/contributors?featured=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $electrical->id);
    }

    public function test_public_detail_returns_media_disciplines_and_seo_without_private_fields(): void
    {
        $media = Media::create(['disk' => 'public', 'path' => 'contributors/profile.jpg', 'filename' => 'profile.jpg', 'original_name' => 'profile.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 100, 'alt_text' => 'Contributor portrait']);
        $contributor = $this->contributor(['slug' => 'multi-discipline-expert', 'media_id' => $media->id, 'email' => 'private@example.test'], ['electrical-engineering', 'power-systems-engineering']);
        $contributor->seo()->create(['meta_title' => 'Engineering Expert', 'robots' => 'index,follow']);

        $this->getJson('/api/v1/contributors/multi-discipline-expert')->assertOk()->assertJsonCount(2, 'data.disciplines')->assertJsonPath('data.photo.alt_text', 'Contributor portrait')->assertJsonPath('data.seo.meta_title', 'Engineering Expert')->assertJsonMissing(['email' => 'private@example.test']);
        $contributor->update(['is_active' => false]);
        app(ContributorCache::class)->flush();
        $this->getJson('/api/v1/contributors/multi-discipline-expert')->assertNotFound();
        $this->getJson('/api/v1/contributors/unknown')->assertNotFound();
    }

    public function test_content_manager_can_create_and_update_sanitized_contributor_with_stable_slug(): void
    {
        $manager = $this->user('content-manager');
        $electrical = EngineeringDiscipline::query()->where('slug', 'electrical-engineering')->firstOrFail();
        $created = $this->actingAs($manager, 'web')->postJson('/api/v1/admin/contributors', [
            'name' => 'Prototype Grid Expert', 'designation' => 'Engineer', 'qualification' => 'M.Tech',
            'biography' => '<p onclick="bad()">Safe biography</p><script>alert(1)</script>', 'expertise_summary' => 'Power systems',
            'discipline_ids' => [$electrical->id], 'primary_discipline_id' => $electrical->id,
        ])->assertCreated()->assertJsonPath('data.slug', 'prototype-grid-expert')->json('data');

        $this->patchJson("/api/v1/admin/contributors/{$created['id']}", ['name' => 'Renamed Grid Expert'])->assertOk()->assertJsonPath('data.slug', 'prototype-grid-expert');
        $stored = Contributor::findOrFail($created['id']);
        $this->assertStringNotContainsString('script', $stored->biography);
        $this->assertStringNotContainsString('onclick', $stored->biography);
    }

    public function test_create_validation_rejects_duplicate_slug_invalid_media_discipline_and_unsafe_urls(): void
    {
        $manager = $this->user('content-manager');
        $existing = $this->contributor(['slug' => 'existing-expert']);
        $this->actingAs($manager, 'web')->postJson('/api/v1/admin/contributors', [
            'name' => 'Invalid', 'slug' => $existing->slug, 'media_id' => 9999, 'discipline_ids' => [9999],
            'primary_discipline_id' => 9999, 'linkedin_url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors(['slug', 'media_id', 'discipline_ids.0', 'primary_discipline_id', 'linkedin_url']);
    }

    public function test_status_feature_and_reorder_invalidate_public_cache(): void
    {
        $manager = $this->user('content-manager');
        $first = $this->contributor(['slug' => 'first-expert', 'sort_order' => 1]);
        $second = $this->contributor(['slug' => 'second-expert', 'sort_order' => 2]);
        $this->getJson('/api/v1/contributors')->assertJsonCount(2, 'data');

        $this->actingAs($manager, 'web')->patchJson("/api/v1/admin/contributors/{$first->id}/status", ['is_active' => false])->assertOk();
        $this->patchJson("/api/v1/admin/contributors/{$second->id}/featured", ['is_featured' => true])->assertOk();
        $this->patchJson('/api/v1/admin/contributors/reorder', ['items' => [['id' => $first->id, 'sort_order' => 2], ['id' => $second->id, 'sort_order' => 1]]])->assertOk();

        $this->getJson('/api/v1/contributors')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $second->id)->assertJsonPath('data.0.is_featured', true);
        $this->assertSame(1, $second->fresh()->sort_order);
    }

    public function test_delete_is_safe_deactivation_and_media_remains_reference_protected(): void
    {
        $administrator = $this->user('administrator');
        $media = Media::create(['disk' => 'public', 'path' => 'contributors/used.jpg', 'filename' => 'used.jpg', 'original_name' => 'used.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 100]);
        $contributor = $this->contributor(['media_id' => $media->id]);

        $this->actingAs($administrator, 'web')->deleteJson("/api/v1/admin/contributors/{$contributor->id}")->assertOk();
        $this->assertFalse($contributor->fresh()->is_active);
        $this->deleteJson("/api/v1/admin/media/{$media->id}")->assertStatus(409);
    }

    public function test_role_permissions_are_conservative_and_customer_and_guest_are_denied(): void
    {
        $contributor = $this->contributor();
        $courseManager = $this->user('course-manager');
        $this->actingAs($courseManager, 'web')->getJson('/api/v1/admin/contributors')->assertOk();
        $this->patchJson("/api/v1/admin/contributors/{$contributor->id}", ['designation' => 'Changed'])->assertForbidden();

        foreach (['support-manager', 'customer'] as $role) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($this->user($role), 'web')->getJson('/api/v1/admin/contributors')->assertForbidden();
        }
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/admin/contributors')->assertUnauthorized();
    }

    public function test_administrator_and_super_admin_can_manage_and_contributor_seo_is_reused(): void
    {
        $contributor = $this->contributor();
        foreach (['administrator', 'super-admin'] as $role) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($this->user($role), 'web')->getJson('/api/v1/admin/contributors')->assertOk();
        }
        $this->putJson("/api/v1/admin/contributors/{$contributor->id}/seo", ['meta_title' => 'Contributor SEO', 'robots' => 'index,follow'])->assertOk();
        $this->getJson("/api/v1/contributors/{$contributor->slug}")->assertJsonPath('data.seo.meta_title', 'Contributor SEO');
    }
}
