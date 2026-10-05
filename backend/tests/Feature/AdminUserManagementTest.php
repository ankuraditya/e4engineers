<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_authorized_user_lists_only_administrative_accounts(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($administrator, 'web')->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $administrator->id)
            ->assertJsonMissing(['email' => $customer->email]);
    }

    public function test_unprivileged_admin_cannot_list_administrative_accounts(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('content-manager');

        $this->actingAs($manager, 'web')->getJson('/api/v1/admin/users')->assertForbidden();
    }

    public function test_super_admin_creates_admin_with_hashed_password_and_role(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin, 'web')->postJson('/api/v1/admin/users', [
            'name' => 'Content Admin',
            'email' => 'content.admin@example.com',
            'mobile' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['content-manager'],
            'status' => 'active',
            'is_admin' => true,
            'permissions' => ['permissions.assign'],
        ])->assertCreated()
            ->assertJsonPath('data.roles.0', 'content-manager')
            ->assertJsonMissingPath('data.password');

        $created = User::query()->where('email', 'content.admin@example.com')->sole();
        $this->assertTrue(Hash::check('password123', $created->password));
        $this->assertTrue($created->hasRole('content-manager'));
        $this->assertSame(UserStatus::Active, $created->status);
        $this->assertFalse($created->hasPermissionTo('permissions.assign'));
    }

    public function test_invalid_role_and_duplicate_email_are_rejected(): void
    {
        $superAdmin = User::factory()->create(['email' => 'existing@example.com']);
        $superAdmin->assignRole('super-admin');

        $payload = [
            'name' => 'Invalid Admin',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['customer'],
        ];

        $this->actingAs($superAdmin, 'web')->postJson('/api/v1/admin/users', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'roles.0']);
    }

    public function test_status_change_suspends_admin_and_blocks_login(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $admin = User::factory()->create(['password' => 'password123']);
        $admin->assignRole('administrator');

        $this->actingAs($superAdmin, 'web')->patchJson("/api/v1/admin/users/{$admin->id}/status", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'password123'])
            ->assertUnauthorized();
    }

    public function test_final_super_admin_cannot_self_suspend_or_lose_role(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin, 'web')->patchJson("/api/v1/admin/users/{$superAdmin->id}/status", ['status' => 'suspended'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->patchJson("/api/v1/admin/users/{$superAdmin->id}", ['roles' => ['administrator']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles']);

        $this->assertTrue($superAdmin->fresh()->hasRole('super-admin'));
    }
}
