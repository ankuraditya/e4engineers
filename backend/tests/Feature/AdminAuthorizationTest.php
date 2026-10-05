<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_system_roles_permissions_and_idempotent_seeding(): void
    {
        $this->seed(AuthorizationSeeder::class);

        $this->assertSame(config('admin_authorization.roles'), Role::query()->orderBy('id')->pluck('name')->all());
        $this->assertSame(count(config('admin_authorization.permissions')), Permission::query()->count());
        $this->assertTrue(Role::findByName('administrator')->hasPermissionTo('admin-users.view'));
        $this->assertFalse(Role::findByName('administrator')->hasPermissionTo('roles.update'));
        $this->assertSame(1, Role::query()->where('name', 'super-admin')->count());
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not authorized to perform this action.');
    }

    public function test_manager_can_access_dashboard_but_not_permission_management(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('content-manager');

        $this->actingAs($manager, 'web')->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/permissions')->assertForbidden();
    }

    public function test_super_admin_gate_bypass_grants_every_defined_ability(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        Role::findByName('super-admin')->syncPermissions([]);

        $this->assertTrue(Gate::forUser($superAdmin)->allows('roles.update'));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('unregistered.future.permission'));
        $this->actingAs($superAdmin, 'web')->getJson('/api/v1/admin/permissions')->assertOk();
    }

    public function test_administrator_cannot_escalate_an_account_to_super_admin(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');

        $this->actingAs($administrator, 'web')->postJson('/api/v1/admin/users', [
            'name' => 'Escalated User',
            'email' => 'escalated@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['super-admin'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_unauthorized_role_cannot_sync_permissions_but_super_admin_can(): void
    {
        $role = Role::findByName('content-manager');
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');

        $this->actingAs($administrator, 'web')->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
            'permissions' => ['admin-users.view'],
        ])->assertForbidden();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $this->app['auth']->forgetGuards();
        $this->actingAs($superAdmin, 'web')->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
            'permissions' => ['admin.dashboard.access', 'admin-users.view'],
        ])->assertOk();

        $this->assertTrue($role->fresh()->hasPermissionTo('admin-users.view'));
    }
}
