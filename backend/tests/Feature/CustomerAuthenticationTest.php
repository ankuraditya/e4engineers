<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(AuthorizationSeeder::class);
        $this->withHeader('Origin', 'http://localhost:4177');
    }

    public function test_customer_registration_normalizes_data_hashes_password_and_authenticates(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '  Ankur Customer  ',
            'email' => 'Customer@Example.COM',
            'mobile' => '+91 98765 43210',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'customer@example.com')
            ->assertJsonPath('data.user.mobile', '9876543210')
            ->assertJsonMissingPath('data.user.password');

        $user = User::query()->sole();
        $this->assertSame('Ankur Customer', $user->name);
        $this->assertNotSame('secure-password', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->isAdmin());
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_registration_validation_and_unique_identity_rules(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'mobile' => '9876543210']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'EXISTING@example.com',
            'mobile' => '9876543210',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name', 'email', 'mobile', 'password']);
    }

    public function test_login_with_email_and_mobile_succeeds_and_updates_last_login(): void
    {
        $emailUser = User::factory()->create(['email' => 'email@example.com', 'password' => 'password123']);
        $emailUser->assignRole('customer');

        $this->postJson('/api/v1/auth/login', ['login' => 'EMAIL@EXAMPLE.COM', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $emailUser->id);

        $this->assertNotNull($emailUser->fresh()->last_login_at);
        $this->postJson('/api/v1/auth/logout')->assertOk();

        $mobileUser = User::factory()->create(['mobile' => '9123456789', 'password' => 'password123']);
        $mobileUser->assignRole('customer');
        $this->postJson('/api/v1/auth/login', ['login' => '91234 56789', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $mobileUser->id);
    }

    public function test_invalid_credentials_and_disabled_accounts_are_rejected_generically(): void
    {
        $active = User::factory()->create(['email' => 'active@example.com', 'password' => 'correct-password']);

        $this->postJson('/api/v1/auth/login', ['login' => $active->email, 'password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid login credentials.');

        foreach ([UserStatus::Inactive, UserStatus::Suspended] as $status) {
            $user = User::factory()->create(['status' => $status, 'password' => 'correct-password']);
            $user->assignRole('customer');
            $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'correct-password'])
                ->assertUnauthorized()
                ->assertJsonPath('message', 'Invalid login credentials.');
        }
    }

    public function test_login_rate_limit_is_enforced(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['login' => 'nobody@example.com', 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', ['login' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Too many requests.');
    }

    public function test_current_user_requires_authentication_and_returns_safe_identity(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');
    }

    public function test_logout_invalidates_the_current_session(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $user->assignRole('customer');
        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password123'])->assertOk();

        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertGuest('web');

        // Feature tests reuse the application container across requests, while a
        // real HTTP request receives a fresh Sanctum request guard.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_forgot_password_uses_a_safe_response_and_laravel_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $safeMessage = 'If an account exists for this email, password reset instructions have been sent.';
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'RESET@example.com'])
            ->assertOk()->assertJsonPath('message', $safeMessage);
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()->assertJsonPath('message', $safeMessage);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user): bool {
            return str_starts_with((string) $notification->toMail($user)->actionUrl, 'http://localhost:4177/reset-password?');
        });
    }

    public function test_password_can_be_reset_with_a_valid_token_and_old_password_stops_working(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com', 'password' => 'old-password']);
        $user->assignRole('customer');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('message', 'Password reset successfully.');

        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'old-password'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'new-password'])->assertOk();
    }

    public function test_invalid_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The password reset token is invalid or has expired.');
    }

    public function test_customer_can_change_password_only_with_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $user->assignRole('customer');

        $this->actingAs($user)->putJson('/api/v1/account/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        $this->putJson('/api/v1/account/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('message', 'Password updated successfully.');

        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
