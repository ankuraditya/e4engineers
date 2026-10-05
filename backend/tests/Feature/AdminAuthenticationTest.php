<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(AuthorizationSeeder::class);
        $this->withHeader('Origin', 'http://localhost:4177');
    }

    public function test_customer_cannot_login_through_admin_endpoint(): void
    {
        $customer = User::factory()->create(['password' => 'password123']);
        $customer->assignRole('customer');

        $this->postJson('/api/v1/admin/auth/login', ['email' => $customer->email, 'password' => 'password123'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid administrative credentials.');
    }

    public function test_admin_only_account_cannot_login_through_customer_endpoint(): void
    {
        $admin = User::factory()->create(['password' => 'password123']);
        $admin->assignRole('administrator');

        $this->postJson('/api/v1/auth/login', ['login' => $admin->email, 'password' => 'password123'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid login credentials.');
    }

    public function test_active_admin_can_login_and_fetch_safe_current_identity(): void
    {
        $admin = User::factory()->create(['password' => 'password123']);
        $admin->assignRole('administrator');

        $this->postJson('/api/v1/admin/auth/login', ['email' => mb_strtoupper($admin->email), 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $admin->id)
            ->assertJsonPath('data.user.roles.0', 'administrator')
            ->assertJsonMissingPath('data.user.password');

        $this->getJson('/api/v1/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $admin->email);
    }

    public function test_wrong_password_and_disabled_admin_accounts_are_rejected(): void
    {
        $admin = User::factory()->create(['password' => 'password123']);
        $admin->assignRole('administrator');

        $this->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertUnauthorized();

        foreach ([UserStatus::Inactive, UserStatus::Suspended] as $status) {
            $admin->update(['status' => $status]);
            $this->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'password123'])
                ->assertUnauthorized()
                ->assertJsonPath('message', 'Invalid administrative credentials.');
        }
    }

    public function test_admin_login_rate_limit_is_enforced(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/admin/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/admin/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_guest_me_is_rejected_and_logout_invalidates_admin_session(): void
    {
        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized();

        $admin = User::factory()->create(['password' => 'password123']);
        $admin->assignRole('administrator');
        $this->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'password123'])->assertOk();

        $this->postJson('/api/v1/admin/auth/logout')->assertOk();
        $this->assertGuest('web');
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
    }
}
