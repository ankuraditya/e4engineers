<?php

namespace Tests\Feature;

use App\Models\EngineeringDiscipline;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EngineeringDisciplineMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class]);
    }

    public function test_public_list_is_ordered_and_hides_inactive_disciplines(): void
    {
        EngineeringDiscipline::query()->where('slug', 'civil-engineering')->update(['is_active' => false]);

        $this->getJson('/api/v1/engineering-disciplines')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'electrical-engineering')
            ->assertJsonMissing(['slug' => 'civil-engineering']);
    }

    public function test_public_detail_uses_slug_and_rejects_unknown_or_inactive_records(): void
    {
        $this->getJson('/api/v1/engineering-disciplines/electrical-engineering')
            ->assertOk()->assertJsonPath('data.name', 'Electrical Engineering');
        $this->getJson('/api/v1/engineering-disciplines/unknown')->assertNotFound();

        EngineeringDiscipline::query()->where('slug', 'electrical-engineering')->update(['is_active' => false]);
        $this->getJson('/api/v1/engineering-disciplines/electrical-engineering')->assertNotFound();
    }

    public function test_super_admin_can_create_with_generated_slug_and_cache_is_invalidated(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $this->getJson('/api/v1/engineering-disciplines')->assertOk();

        $this->actingAs($superAdmin, 'web')->postJson('/api/v1/admin/engineering-disciplines', [
            'name' => 'Chemical Engineering', 'sort_order' => 9,
        ])->assertCreated()->assertJsonPath('data.slug', 'chemical-engineering');

        $this->getJson('/api/v1/engineering-disciplines')->assertJsonFragment(['slug' => 'chemical-engineering']);
    }

    public function test_duplicate_slug_is_rejected_and_name_update_keeps_slug_stable(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $record = EngineeringDiscipline::query()->where('slug', 'electrical-engineering')->firstOrFail();

        $this->actingAs($superAdmin, 'web')->postJson('/api/v1/admin/engineering-disciplines', [
            'name' => 'Duplicate', 'slug' => 'electrical-engineering',
        ])->assertUnprocessable()->assertJsonValidationErrors(['slug']);

        $this->patchJson("/api/v1/admin/engineering-disciplines/{$record->id}", ['name' => 'Electrical and Energy Engineering'])
            ->assertOk()->assertJsonPath('data.slug', 'electrical-engineering');
    }

    public function test_status_and_reorder_endpoints_change_public_behavior_atomically(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $electrical = EngineeringDiscipline::query()->where('slug', 'electrical-engineering')->firstOrFail();
        $mechanical = EngineeringDiscipline::query()->where('slug', 'mechanical-engineering')->firstOrFail();

        $this->actingAs($superAdmin, 'web')->patchJson("/api/v1/admin/engineering-disciplines/{$electrical->id}/status", ['is_active' => false])->assertOk();
        $this->getJson('/api/v1/engineering-disciplines')->assertJsonMissing(['id' => $electrical->id]);

        $this->patchJson('/api/v1/admin/engineering-disciplines/reorder', ['items' => [
            ['id' => $electrical->id, 'sort_order' => 2], ['id' => $mechanical->id, 'sort_order' => 1],
        ]])->assertOk();
        $this->assertSame(1, $mechanical->fresh()->sort_order);
    }

    public function test_admin_list_includes_inactive_and_customer_is_forbidden(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');
        EngineeringDiscipline::query()->first()->update(['is_active' => false]);

        $this->actingAs($administrator, 'web')->getJson('/api/v1/admin/engineering-disciplines')
            ->assertOk()->assertJsonCount(8, 'data');

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->app['auth']->forgetGuards();
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/engineering-disciplines')->assertForbidden();

    }
}
