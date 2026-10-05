<?php

namespace App\Policies;

use App\Models\User;

final class AdminUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin-users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('admin-users.view') && $model->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('admin-users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('admin-users.update') && $model->isAdmin();
    }

    public function changeStatus(User $user, User $model): bool
    {
        return $model->isAdmin() && ($user->can('admin-users.activate') || $user->can('admin-users.suspend'));
    }
}
