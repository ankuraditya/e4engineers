<?php

namespace Tests\Feature;

use App\Enums\EntitlementSource;
use App\Models\DigitalEntitlement;
use App\Models\DigitalResource;
use App\Models\EngineeringDiscipline;
use App\Models\Media;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\EntitlementService;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Database\Seeders\ResourceTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DigitalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class, ResourceTypesSeeder::class]);
        Storage::fake('private');
    }

    private function user(string $role = 'customer'): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function content(string $access = 'free'): DigitalResource
    {
        $path = 'files/'.uniqid().'.pdf';
        Storage::disk('private')->put($path, 'secure-pdf');
        $m = Media::create(['disk' => 'private', 'path' => $path, 'filename' => basename($path), 'original_name' => 'test.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 10]);

        return DigitalResource::factory()->published()->create(['access_type' => $access, 'price' => $access === 'paid' ? 499 : null, 'file_media_id' => $m->id, 'file_format' => 'PDF', 'resource_type_id' => ResourceType::first()->id, 'engineering_discipline_id' => EngineeringDiscipline::first()->id]);
    }

    public function test_free_guest_and_login_required_customer_download_through_controlled_route(): void
    {
        $free = $this->content();
        $this->get("/api/v1/resources/{$free->slug}/download")->assertOk()->assertHeader('x-content-type-options', 'nosniff');
        $this->assertDatabaseCount('digital_download_logs', 1);
        $login = $this->content('login_required');
        $this->get("/api/v1/resources/{$login->slug}/download")->assertUnauthorized();
        $customer = $this->user();
        $this->actingAs($customer, 'web')->get("/api/v1/resources/{$login->slug}/download")->assertOk();
        $this->assertDatabaseCount('digital_download_logs', 2);
    }

    public function test_paid_access_requires_the_correct_users_valid_entitlement(): void
    {
        $paid = $this->content('paid');
        $a = $this->user();
        $b = $this->user();
        $service = app(EntitlementService::class);
        $e = $service->grant($a, $paid, EntitlementSource::AdminGrant);
        $this->actingAs($b, 'web')->get("/api/v1/resources/{$paid->slug}/download")->assertForbidden();
        $this->assertDatabaseCount('digital_download_logs', 0);
        $this->app['auth']->forgetGuards();
        $this->actingAs($a, 'web')->get("/api/v1/resources/{$paid->slug}/download")->assertOk();
        $this->assertDatabaseHas('digital_download_logs', ['user_id' => $a->id, 'entitlement_id' => $e->id]);
        $service->revoke($e, $this->user('administrator'));
        $this->app['auth']->forgetGuards();
        $this->actingAs($a, 'web')->get("/api/v1/resources/{$paid->slug}/download")->assertForbidden();
    }

    public function test_expired_entitlement_and_missing_file_are_denied_without_success_log(): void
    {
        $paid = $this->content('paid');
        $u = $this->user();
        DigitalEntitlement::factory()->create(['user_id' => $u->id, 'entitleable_type' => 'resource', 'entitleable_id' => $paid->id, 'expires_at' => now()->subMinute()]);
        $this->actingAs($u, 'web')->get("/api/v1/resources/{$paid->slug}/download")->assertForbidden();
        $free = $this->content();
        Storage::disk('private')->delete($free->fileMedia->path);
        $this->get("/api/v1/resources/{$free->slug}/download")->assertNotFound();
        $this->assertDatabaseCount('digital_download_logs', 0);
    }

    public function test_admin_grant_is_idempotent_revoke_is_immediate_and_customer_library_is_idor_safe(): void
    {
        $paid = $this->content('paid');
        $customer = $this->user();
        $other = $this->user();
        $admin = $this->user('publication-manager');
        $payload = ['user_id' => $customer->id, 'content_type' => 'resource', 'content_id' => $paid->id];
        $id = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/entitlements', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/admin/entitlements', $payload)->assertCreated();
        $this->assertDatabaseCount('digital_entitlements', 1);
        $this->app['auth']->forgetGuards();
        $this->actingAs($customer, 'web')->getJson('/api/v1/account/digital-resources')->assertOk()->assertJsonCount(1, 'data');
        $this->app['auth']->forgetGuards();
        $this->actingAs($other, 'web')->getJson('/api/v1/account/digital-resources')->assertOk()->assertJsonCount(0, 'data');
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin, 'web')->patchJson("/api/v1/admin/entitlements/{$id}/revoke", ['reason' => 'Test'])->assertOk()->assertJsonPath('data.status', 'revoked');
    }

    public function test_customer_cannot_manage_entitlements_and_private_metadata_never_leaks(): void
    {
        $paid = $this->content('paid');
        $customer = $this->user();
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/entitlements')->assertForbidden();
        $json = $this->getJson("/api/v1/resources/{$paid->slug}")->assertOk()->getContent();
        $this->assertStringNotContainsString('files/test.pdf', $json);
        $this->assertStringNotContainsString('"disk"', $json);
    }
}
