<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class RegisterCustomer
{
    public function handle(array $validated): User
    {
        return DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'password' => $validated['password'],
                'status' => UserStatus::Active,
            ]);

            $user->assignRole(Role::findOrCreate('customer', 'web'));

            return $user;
        });
    }
}
