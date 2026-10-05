<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CustomerAddress;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Notifications\SetAccountPasswordNotification;
use App\Services\InventoryService;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\ShippingProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CheckoutOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, ShippingProviderSeeder::class]);
        ShippingSetting::current()->update(['shipping_enabled' => true, 'mode' => 'flat_rate', 'flat_shipping_enabled' => true, 'flat_shipping_charge' => 80]);
    }

    public function test_guest_checkout_creates_account_order_snapshots_and_deducts_inventory(): void
    {
        Notification::fake();
        [$book, $token, $quote] = $this->checkoutFixture();

        $response = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote));

        $response->assertCreated()->assertJsonPath('data.account_created', true)->assertJsonPath('data.order.status', 'confirmed')->assertJsonPath('data.order.payment_status', 'cod_pending');
        $user = User::where('email', 'guest@example.com')->firstOrFail();
        $this->assertTrue($user->password_setup_required);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'subtotal' => 1000, 'shipping_total' => 80, 'grand_total' => 1080]);
        $this->assertDatabaseHas('order_addresses', ['postal_code' => '110001', 'name' => 'Guest Engineer']);
        $this->assertDatabaseHas('customer_addresses', ['user_id' => $user->id, 'postal_code' => '110001']);
        $this->assertSame(4, $book->inventory->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['book_id' => $book->id, 'type' => 'sale', 'quantity' => -1]);
        Notification::assertSentTo($user, SetAccountPasswordNotification::class);
    }

    public function test_existing_guest_identity_is_not_logged_in_or_duplicated(): void
    {
        User::factory()->create(['email' => 'guest@example.com', 'mobile' => '9876543210']);
        [, $token, $quote] = $this->checkoutFixture();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote))->assertCreated()->assertJsonPath('data.existing_account_detected', true);
        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'guest@example.com')->count());
        $this->assertNull(Order::firstOrFail()->user_id);
    }

    public function test_server_ignores_tampered_totals_and_idempotency_returns_one_order(): void
    {
        [, $token, $quote] = $this->checkoutFixture();
        $payload = $this->payload($quote) + ['subtotal' => 1, 'discount' => 9999, 'shipping' => 1, 'total' => 1, 'book_price' => 1, 'stock' => 999];
        $first = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $payload)->assertCreated();
        $this->assertSame('1080.00', $first->json('data.order.pricing.total'));
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $payload)->assertCreated();
        $this->assertSame(1, Order::count());
    }

    public function test_coupon_usage_and_guest_order_token_are_protected(): void
    {
        User::factory()->create(['email' => 'guest@example.com', 'mobile' => '9876543210']);
        [$book, $token] = $this->checkoutFixture(false);
        Coupon::factory()->create(['code' => 'SAVE100', 'discount_type' => 'fixed', 'discount_value' => 100]);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/coupon', ['code' => 'SAVE100'])->assertOk();
        $quote = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['postal_code' => '110001', 'payment_mode' => 'cod'])->json('data.selected_quote.quote_id');
        $placed = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote))->assertCreated();
        $number = $placed->json('data.order.order_number');
        $access = $placed->json('data.order_access_token');
        $this->assertSame(1, CouponUsage::count());
        $this->getJson("/api/v1/orders/$number/success")->assertNotFound();
        $this->getJson("/api/v1/orders/$number/success?token=$access")->assertOk();
        $this->assertSame(4, $book->inventory->fresh()->stock_quantity);
    }

    public function test_authenticated_address_idor_and_order_idor_are_blocked(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $address = CustomerAddress::factory()->create(['user_id' => $owner->id]);
        [, , $quote] = $this->checkoutFixture(authenticated: $attacker);
        $payload = $this->payload($quote);
        unset($payload['shipping_address']);
        $payload['address_id'] = $address->id;
        $this->actingAs($attacker)->postJson('/api/v1/checkout/place-order', $payload)->assertNotFound();
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $this->getJson("/api/v1/account/orders/$order->order_number")->assertNotFound();
    }

    public function test_admin_status_transition_restores_inventory_exactly_once(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('order-manager');
        [$book,$token,$quote] = $this->checkoutFixture();
        $number = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote))->assertCreated()->json('data.order.order_number');
        $this->actingAs($admin)->patchJson("/api/v1/admin/orders/$number/status", ['status' => 'cancelled', 'note' => 'Customer request'])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(5, $book->inventory->fresh()->stock_quantity);
        $this->patchJson("/api/v1/admin/orders/$number/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame(5, $book->inventory->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::where('type', 'cancellation_restore')->where('reference_id', $number)->count());
    }

    private function checkoutFixture(bool $quote = true, ?User $authenticated = null): array
    {
        $book = Book::factory()->create(['selling_price' => 1000, 'mrp' => 1200]);
        app(InventoryService::class)->increase($book, 5, InventoryMovementType::InitialStock, 'Fixture');
        $token = Str::random(64);
        if ($authenticated) {
            $this->actingAs($authenticated);
        }
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 1])->assertCreated();
        $quoteId = $quote ? $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['postal_code' => '110001', 'payment_mode' => 'cod'])->assertOk()->json('data.selected_quote.quote_id') : null;

        return [$book, $token, $quoteId];
    }

    private function payload(string $quote): array
    {
        return ['contact' => ['name' => 'Guest Engineer', 'email' => 'GUEST@example.com', 'mobile' => '9876543210'], 'shipping_address' => ['type' => 'home', 'full_name' => 'Guest Engineer', 'mobile' => '9876543210', 'address_line_1' => '12 Test Road', 'city' => 'New Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country_code' => 'IN'], 'shipping_quote_id' => $quote, 'payment_method' => 'cod', 'idempotency_key' => (string) Str::uuid()];
    }
}
