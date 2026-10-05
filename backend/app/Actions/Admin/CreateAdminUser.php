<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CreateAdminUser
{
    public function handle(User $actor, array $validated): User
    {
        $this->authorizeRoles($actor, $validated['roles']);

        return DB::transaction(function () use ($actor, $validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'password' => $validated['password'],
                'status' => UserStatus::Active,
            ]);

            $user->syncRoles($validated['roles']);

            Log::notice('Administrative user created.', [
                'actor_user_id' => $actor->id,
                'admin_user_id' => $user->id,
                'roles' => $validated['roles'],
            ]);

            return $user->load('roles', 'permissions');
        });
    }

    /** @param array<int, string> $roles */
    private function authorizeRoles(User $actor, array $roles): void
    {
        if (! $actor->can('roles.assign')) {
            throw new AuthorizationException('You are not authorized to assign administrative roles.');
        }

        if (in_array('super-admin', $roles, true) && ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('Only a Super Admin may assign the Super Admin role.');
        }
    }
}
