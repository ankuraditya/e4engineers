<?php

namespace Tests\Feature;

use App\Models\CourseLevel;
use App\Models\PublicationType;
use App\Models\ResourceType;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\CourseLevelsSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Database\Seeders\PublicationTypesSeeder;
use Database\Seeders\ResourceTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StaticMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            AuthorizationSeeder::class,
            EngineeringDisciplinesSeeder::class,
            CourseLevelsSeeder::class,
            ResourceTypesSeeder::class,
            PublicationTypesSeeder::class,
        ]);
    }

    public function test_reference_seeders_are_idempotent_and_public_lists_are_ordered(): void
    {
        $this->seed([
            EngineeringDisciplinesSeeder::class,
            CourseLevelsSeeder::class,
            ResourceTypesSeeder::class,
            PublicationTypesSeeder::class,
        ]);

        $this->assertDatabaseCount('engineering_disciplines', 8);
        $this->assertDatabaseCount('course_levels', 3);
        $this->assertDatabaseCount('resource_types', 8);
        $this->assertDatabaseCount('publication_types', 6);

        $this->getJson('/api/v1/course-levels')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'beginner')
            ->assertJsonPath('data.2.slug', 'advanced');
        $this->getJson('/api/v1/resource-types')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'lecture-notes');
        $this->getJson('/api/v1/publication-types')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'journal');
    }

    public function test_domain_managers_can_manage_only_their_assigned_reference_data(): void
    {
        $courseManager = User::factory()->create();
        $courseManager->assignRole('course-manager');

        $this->actingAs($courseManager, 'web')->postJson('/api/v1/admin/course-levels', [
            'name' => 'Expert',
            'sort_order' => 4,
        ])->assertCreated()->assertJsonPath('data.slug', 'expert');
        $this->postJson('/api/v1/admin/resource-types', ['name' => 'Forbidden'])->assertForbidden();

        $publicationManager = User::factory()->create();
        $publicationManager->assignRole('publication-manager');
        $this->app['auth']->forgetGuards();

        $this->actingAs($publicationManager, 'web')->postJson('/api/v1/admin/resource-types', [
            'name' => 'Simulation Files',
        ])->assertCreated();
        $this->postJson('/api/v1/admin/publication-types', [
            'name' => 'Conference Proceedings',
        ])->assertCreated();
        $this->postJson('/api/v1/admin/course-levels', ['name' => 'Forbidden'])->assertForbidden();
    }

    public function test_status_and_reorder_updates_are_reflected_in_public_course_levels(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');
        $beginner = CourseLevel::query()->where('slug', 'beginner')->firstOrFail();
        $advanced = CourseLevel::query()->where('slug', 'advanced')->firstOrFail();

        $this->actingAs($administrator, 'web')
            ->patchJson("/api/v1/admin/course-levels/{$beginner->id}/status", ['is_active' => false])
            ->assertOk();
        $this->getJson('/api/v1/course-levels')->assertJsonMissing(['id' => $beginner->id]);

        $this->patchJson('/api/v1/admin/course-levels/reorder', ['items' => [
            ['id' => $beginner->id, 'sort_order' => 3],
            ['id' => $advanced->id, 'sort_order' => 1],
        ]])->assertOk();

        $this->assertSame(1, $advanced->fresh()->sort_order);
        $this->assertFalse($beginner->fresh()->is_active);
    }

    public function test_inactive_reference_records_are_hidden_publicly_but_visible_to_authorized_admins(): void
    {
        $publicationManager = User::factory()->create();
        $publicationManager->assignRole('publication-manager');
        $resourceType = ResourceType::query()->firstOrFail();
        $publicationType = PublicationType::query()->firstOrFail();
        $resourceType->update(['is_active' => false]);
        $publicationType->update(['is_active' => false]);

        $this->getJson('/api/v1/resource-types')->assertJsonMissing(['id' => $resourceType->id]);
        $this->getJson('/api/v1/publication-types')->assertJsonMissing(['id' => $publicationType->id]);

        $this->actingAs($publicationManager, 'web')->getJson('/api/v1/admin/resource-types')
            ->assertOk()->assertJsonFragment(['id' => $resourceType->id]);
        $this->getJson('/api/v1/admin/publication-types')
            ->assertOk()->assertJsonFragment(['id' => $publicationType->id]);
    }
}
