<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class ChangeAdminUserStatus
{
    public function handle(User $actor, User $user, UserStatus $status): User
    {
        if ($actor->is($user) && $status !== UserStatus::Active) {
            throw ValidationException::withMessages(['status' => ['You cannot suspend or deactivate your own account.']]);
        }

        if ($user->hasRole('super-admin') && $status !== UserStatus::Active && User::role('super-admin')->where('status', UserStatus::Active)->count() <= 1) {
            throw ValidationException::withMessages(['status' => ['The final active Super Admin cannot be suspended or deactivated.']]);
        }

        $user->update(['status' => $status]);

        Log::notice('Administrative user status changed.', [
            'actor_user_id' => $actor->id,
            'admin_user_id' => $user->id,
            'status' => $status->value,
        ]);

        return $user->load('roles', 'permissions');
    }
}
