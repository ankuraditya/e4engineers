<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuthorizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('admin_authorization.permissions') as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('admin_authorization.roles') as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $permissionNames = config("admin_authorization.role_permissions.{$roleName}", []);

            if ($permissionNames === ['*']) {
                $permissionNames = config('admin_authorization.permissions');
            }

            if ($permissionNames !== []) {
                $role->givePermissionTo($permissionNames);
            }
        }

        User::query()->doesntHave('roles')->eachById(function (User $user): void {
            $user->assignRole('customer');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
