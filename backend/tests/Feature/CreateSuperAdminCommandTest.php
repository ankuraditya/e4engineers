<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_command_creates_super_admin_without_printing_password(): void
    {
        $this->artisan('e4engineers:create-super-admin')
            ->expectsQuestion('Name', 'Initial Admin')
            ->expectsQuestion('Email', 'INITIAL@EXAMPLE.COM')
            ->expectsQuestion('Mobile', '9876543210')
            ->expectsQuestion('Password', 'secret-password')
            ->expectsQuestion('Confirm password', 'secret-password')
            ->expectsOutput('Super Admin created successfully.')
            ->doesntExpectOutput('secret-password')
            ->assertSuccessful();

        $user = User::query()->where('email', 'initial@example.com')->sole();
        $this->assertTrue($user->hasRole('super-admin'));
    }

    public function test_command_rejects_invalid_input(): void
    {
        $this->artisan('e4engineers:create-super-admin')
            ->expectsQuestion('Name', '')
            ->expectsQuestion('Email', 'invalid')
            ->expectsQuestion('Mobile', '123')
            ->expectsQuestion('Password', 'short')
            ->expectsQuestion('Confirm password', 'different')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_user_requires_confirmation_before_promotion(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('e4engineers:create-super-admin')
            ->expectsQuestion('Name', $user->name)
            ->expectsQuestion('Email', $user->email)
            ->expectsQuestion('Mobile', '')
            ->expectsConfirmation('This user already exists. Assign the Super Admin role without changing the password?', 'no')
            ->expectsOutput('No changes were made.')
            ->assertFailed();

        $this->assertFalse($user->fresh()->hasRole('super-admin'));
    }
}
