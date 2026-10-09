<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use App\Models\Order;
use App\Models\ShippingPickupLocation;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\ShippingProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SelfCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, ShippingProviderSeeder::class]);
    }

    public function test_self_collection_is_hidden_until_an_active_location_and_hours_are_enabled(): void
    {
        $this->getJson('/api/v1/shipping/self-collection')->assertOk()->assertJsonPath('data', null);
        $location = $this->location();
        ShippingSetting::current()->update(['self_collect_enabled' => true, 'self_collect_location_id' => $location->id, 'self_collect_hours' => 'Monday–Friday, 10 am–5 pm']);
        $this->getJson('/api/v1/shipping/self-collection')->assertOk()->assertJsonPath('data.name', 'Campus collection desk');
        $location->update(['is_active' => false]);
        $this->getJson('/api/v1/shipping/self-collection')->assertOk()->assertJsonPath('data', null);
    }

    public function test_guest_can_place_zero_shipping_self_collect_order_and_admin_can_complete_it(): void
    {
        Notification::fake();
        $location = $this->location();
        ShippingSetting::current()->update(['self_collect_enabled' => true, 'self_collect_location_id' => $location->id, 'self_collect_hours' => 'Monday–Friday, 10 am–5 pm']);
        $book = Book::factory()->create(['selling_price' => 250, 'mrp' => 399]);
        app(InventoryService::class)->increase($book, 5, InventoryMovementType::InitialStock, 'Fixture');
        $token = Str::random(64);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 1])->assertCreated();
        $quote = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['delivery_method' => 'self_collect', 'payment_mode' => 'cod'])->assertOk()->assertJsonPath('data.selected_quote.shipping_charge', '0.00')->json('data.selected_quote.quote_id');
        $payload = ['contact' => ['name' => 'Student Buyer', 'email' => 'student@example.com', 'mobile' => '9876543210'], 'delivery_method' => 'self_collect', 'shipping_quote_id' => $quote, 'payment_method' => 'cod', 'idempotency_key' => (string) Str::uuid()];
        $number = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $payload)->assertCreated()->assertJsonPath('data.order.delivery_method', 'self_collect')->assertJsonPath('data.order.pricing.shipping', '0.00')->json('data.order.order_number');
        $order = Order::where('order_number', $number)->firstOrFail();
        $this->assertSame('Monday–Friday, 10 am–5 pm', $order->shipping_snapshot['pickup']['hours']);
        $this->assertDatabaseMissing('customer_addresses', ['user_id' => $order->user_id]);

        $admin = User::factory()->create();
        $admin->assignRole('order-manager');
        $this->actingAs($admin)->postJson("/api/v1/admin/orders/$number/pickup-ready")->assertOk()->assertJsonPath('data.status', 'packed');
        $this->postJson("/api/v1/admin/orders/$number/pickup-collected")->assertOk()->assertJsonPath('data.status', 'delivered')->assertJsonPath('data.payment_status', 'paid');
        $this->postJson("/api/v1/admin/orders/$number/pickup-collected")->assertOk();
        $this->assertSame(1, $order->fresh()->histories()->where('note', 'Payment received at collection')->count());
    }

    private function location(): ShippingPickupLocation
    {
        return ShippingPickupLocation::create([
            'name' => 'Campus collection desk', 'contact_name' => 'Store manager', 'phone' => '9876543210',
            'address_line1' => 'College Road', 'city' => 'Dhanbad', 'state' => 'Jharkhand',
            'postal_code' => '828104', 'country' => 'IN', 'is_active' => true,
        ]);
    }
}
