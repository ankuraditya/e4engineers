<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update') && ! in_array($role->name, config('admin_authorization.protected_roles'), true);
    }

    public function syncPermissions(User $user, Role $role): bool
    {
        return $user->can('permissions.assign') && ! in_array($role->name, config('admin_authorization.protected_roles'), true);
    }
}
