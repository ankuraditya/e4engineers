<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    private function customer(array $data = []): User
    {
        $u = User::factory()->create($data);
        $u->assignRole('customer');

        return $u;
    }

    private function address(array $extra = []): array
    {
        return array_merge(['type' => 'home', 'full_name' => 'Test Recipient', 'mobile' => '9876543210', 'address_line_1' => '123 Test Road', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country_code' => 'IN'], $extra);
    }

    public function test_new_customer_dashboard_never_shows_another_customers_orders(): void
    {
        $owner = $this->customer();
        $newCustomer = $this->customer();
        $order = Order::factory()->create(['user_id' => $owner->id, 'status' => OrderStatus::Delivered]);

        $this->getJson('/api/v1/account/dashboard')->assertUnauthorized();
        $this->actingAs($newCustomer, 'web')->getJson('/api/v1/account/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 0)
            ->assertJsonPath('data.orders_in_progress', 0)
            ->assertJsonPath('data.delivered_orders', 0)
            ->assertJsonPath('data.digital_resources', 0)
            ->assertJsonCount(0, 'data.recent_orders')
            ->assertDontSee($order->order_number);

        $this->app['auth']->forgetGuards();
        $this->actingAs($owner, 'web')->getJson('/api/v1/account/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonPath('data.delivered_orders', 1)
            ->assertJsonPath('data.recent_orders.0.order_number', $order->order_number);
    }

    public function test_profile_is_private_and_updates_normalized_identity(): void
    {
        $user = $this->customer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->getJson('/api/v1/account/profile')->assertUnauthorized();
        $this->actingAs($user, 'web')->getJson('/api/v1/account/profile')->assertOk()->assertJsonPath('data.email', $user->email)->assertJsonMissingPath('data.password');
        $this->patchJson('/api/v1/account/profile', ['name' => 'Updated Customer', 'email' => 'NEW@example.com', 'mobile' => '+91 98765 43211', 'status' => 'suspended', 'roles' => ['super-admin']])->assertOk()->assertJsonPath('data.email', 'new@example.com')->assertJsonPath('data.mobile', '9876543211')->assertJsonPath('data.email_verified_at', null);
        $this->assertTrue($user->refresh()->hasRole('customer'));
    }

    public function test_profile_uniqueness_and_mobile_validation(): void
    {
        $this->customer(['email' => 'taken@example.com', 'mobile' => '9876543212']);
        $user = $this->customer();
        $this->actingAs($user, 'web')->patchJson('/api/v1/account/profile', ['name' => 'Me', 'email' => 'taken@example.com', 'mobile' => '123'])->assertUnprocessable()->assertJsonValidationErrors(['email', 'mobile']);
    }

    public function test_address_default_lifecycle_and_validation(): void
    {
        $user = $this->customer();
        $first = $this->actingAs($user, 'web')->postJson('/api/v1/account/addresses', $this->address())->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');
        $second = $this->postJson('/api/v1/account/addresses', $this->address(['type' => 'office', 'address_line_1' => 'Office', 'is_default' => true]))->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');
        $this->assertDatabaseHas('customer_addresses', ['id' => $first, 'is_default' => false]);
        $this->deleteJson("/api/v1/account/addresses/$second")->assertOk();
        $this->assertDatabaseHas('customer_addresses', ['id' => $first, 'is_default' => true]);
        $this->postJson('/api/v1/account/addresses', $this->address(['postal_code' => '012345', 'mobile' => '123']))->assertUnprocessable()->assertJsonValidationErrors(['postal_code', 'mobile']);
    }

    public function test_owner_can_update_and_set_default(): void
    {
        $user = $this->customer();
        $a = CustomerAddress::factory()->default()->create(['user_id' => $user->id]);
        $b = CustomerAddress::factory()->office()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'web')->patchJson("/api/v1/account/addresses/{$b->id}", $this->address(['type' => 'other', 'city' => 'Mumbai']))->assertOk()->assertJsonPath('data.city', 'Mumbai');
        $this->patchJson("/api/v1/account/addresses/{$b->id}/default")->assertOk()->assertJsonPath('data.is_default', true);
        $this->assertFalse($a->fresh()->is_default);
    }

    public function test_idor_is_blocked_for_every_address_operation(): void
    {
        $owner = $this->customer();
        $attacker = $this->customer();
        $address = CustomerAddress::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($attacker, 'web')->getJson("/api/v1/account/addresses/{$address->id}")->assertNotFound();
        $this->putJson("/api/v1/account/addresses/{$address->id}", $this->address())->assertNotFound();
        $this->patchJson("/api/v1/account/addresses/{$address->id}", $this->address())->assertNotFound();
        $this->patchJson("/api/v1/account/addresses/{$address->id}/default")->assertNotFound();
        $this->deleteJson("/api/v1/account/addresses/{$address->id}")->assertNotFound();
        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id, 'deleted_at' => null]);
    }
}
