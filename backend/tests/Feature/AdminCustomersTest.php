<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminCustomersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_admin_can_search_registered_customers_without_listing_staff(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $customer = User::factory()->create(['name' => 'Test Student', 'email' => 'student@example.com', 'mobile' => '9876543210']);
        $customer->assignRole('customer');

        $this->actingAs($admin, 'web')->getJson('/api/v1/admin/customers?search=9876543210')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', 'student@example.com')
            ->assertJsonPath('data.0.orders_count', 0)
            ->assertJsonMissingPath('data.0.password');

        $this->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('data.counts.users', 1);
    }

    public function test_customers_and_unprivileged_staff_cannot_read_customer_records(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/customers')->assertForbidden();

        $staff = User::factory()->create();
        $staff->assignRole('content-manager');
        $this->actingAs($staff, 'web')->getJson('/api/v1/admin/customers')->assertForbidden();
    }
}
