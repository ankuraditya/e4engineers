<?php

namespace App\Actions\Admin;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class SyncRolePermissions
{
    /** @param array<int, string> $permissions */
    public function handle(Role $role, array $permissions): Role
    {
        return DB::transaction(function () use ($role, $permissions): Role {
            $role->syncPermissions($permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->load('permissions');
        });
    }
}
