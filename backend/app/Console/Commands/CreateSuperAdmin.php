<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

#[Signature('e4engineers:create-super-admin')]
#[Description('Securely create or promote the first E4ENGINEERS Super Admin')]
final class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $name = trim((string) $this->ask('Name'));
        $email = mb_strtolower(trim((string) $this->ask('Email')));
        $mobileInput = trim((string) $this->ask('Mobile'));
        $mobile = preg_replace('/\D+/', '', $mobileInput) ?: null;
        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser !== null) {
            if (! $this->confirm('This user already exists. Assign the Super Admin role without changing the password?', false)) {
                $this->warn('No changes were made.');

                return self::FAILURE;
            }

            Role::findOrCreate('super-admin', 'web');
            $existingUser->assignRole('super-admin');
            $existingUser->update(['status' => UserStatus::Active]);
            $this->info('The existing user is now an active Super Admin.');

            return self::SUCCESS;
        }

        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');
        $validator = Validator::make(compact('name', 'email', 'mobile', 'password', 'passwordConfirmation'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $mobile, $password): void {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'password' => $password,
                'status' => UserStatus::Active,
            ]);
            $user->assignRole(Role::findOrCreate('super-admin', 'web'));
        });

        $this->info('Super Admin created successfully.');

        return self::SUCCESS;
    }
}
