<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class LoginCustomer
{
    public function handle(string $login, string $password, bool $remember = false): ?User
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $user = User::query()->where($field, $login)->first();

        if ($user === null || ! Hash::check($password, $user->password) || $user->status !== UserStatus::Active || ! $user->hasRole('customer')) {
            return null;
        }

        Auth::guard('web')->login($user, $remember);
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
