<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class UpdateAdminUser
{
    public function handle(User $actor, User $user, array $validated): User
    {
        if (array_key_exists('roles', $validated)) {
            $this->authorizeRoleUpdate($actor, $user, $validated['roles']);
        }

        return DB::transaction(function () use ($actor, $user, $validated): User {
            $user->update(Arr::only($validated, ['name', 'email', 'mobile']));

            if (array_key_exists('roles', $validated)) {
                $user->syncRoles($validated['roles']);
                Log::notice('Administrative user roles changed.', [
                    'actor_user_id' => $actor->id,
                    'admin_user_id' => $user->id,
                    'roles' => $validated['roles'],
                ]);
            }

            return $user->load('roles', 'permissions');
        });
    }

    /** @param array<int, string> $roles */
    private function authorizeRoleUpdate(User $actor, User $user, array $roles): void
    {
        if (! $actor->can('roles.assign')) {
            throw new AuthorizationException('You are not authorized to assign administrative roles.');
        }

        if (($user->hasRole('super-admin') || in_array('super-admin', $roles, true)) && ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('Only a Super Admin may change the Super Admin role.');
        }

        if ($user->hasRole('super-admin') && ! in_array('super-admin', $roles, true) && User::role('super-admin')->count() <= 1) {
            throw ValidationException::withMessages(['roles' => ['The final Super Admin role cannot be removed.']]);
        }
    }
}
