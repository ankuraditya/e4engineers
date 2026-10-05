<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\InventoryMovement;
use App\Models\ShippingPickupLocation;
use App\Models\ShippingProvider;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\Shipping\NimbusPostShippingProvider;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\ShippingProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ShippingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, ShippingProviderSeeder::class]);
    }

    private function book(int $price = 1000, int $weight = 400): Book
    {
        $b = Book::factory()->create(['selling_price' => $price, 'mrp' => $price, 'weight_grams' => $weight]);
        app(InventoryService::class)->increase($b, 10, InventoryMovementType::InitialStock, 'Fixture');

        return $b;
    }

    private function cart(Book $b, ?User $u = null): string
    {
        $token = Str::random(64);
        if ($u) {
            $this->actingAs($u);
        }$this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $b->id, 'quantity' => 1])->assertCreated();

        return $token;
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('administrator');

        return $u;
    }

    public function test_credentials_are_encrypted_masked_and_blank_secret_is_retained(): void
    {
        $p = ShippingProvider::where('code', 'SHIPROCKET')->first();
        $this->actingAs($this->admin())->putJson("/api/v1/admin/shipping/providers/$p->id/credentials", ['email' => 'api@example.com', 'password' => 'top-secret'])->assertOk()->assertJsonMissing(['password' => 'top-secret'])->assertJsonPath('data.configuration.password_configured', true);
        $raw = DB::table('shipping_providers')->where('id', $p->id)->value('configuration');
        $this->assertStringNotContainsString('top-secret', $raw);
        $this->putJson("/api/v1/admin/shipping/providers/$p->id/credentials", ['email' => 'new@example.com', 'password' => ''])->assertOk();
        $this->assertSame('top-secret', $p->fresh()->configuration['password']);
    }

    public function test_toggle_requires_connection_and_default_is_transactional(): void
    {
        $admin = $this->admin();
        $n = ShippingProvider::where('code', 'NIMBUSPOST')->first();
        $this->actingAs($admin)->patchJson("/api/v1/admin/shipping/providers/$n->id/toggle", ['enabled' => true])->assertUnprocessable();
        $n->update(['connection_status' => 'connected']);
        $this->patchJson("/api/v1/admin/shipping/providers/$n->id/toggle", ['enabled' => true])->assertOk();
        $this->patchJson("/api/v1/admin/shipping/providers/$n->id/default")->assertOk();
        $this->assertSame(1, ShippingProvider::where('is_default', true)->count());
        $this->patchJson("/api/v1/admin/shipping/providers/$n->id/toggle", ['enabled' => false])->assertUnprocessable();
    }

    public function test_flat_free_coupon_interaction_and_no_inventory_mutation(): void
    {
        $b = $this->book(1200);
        $token = $this->cart($b);
        $s = ShippingSetting::current();
        $s->update(['shipping_enabled' => true, 'mode' => 'flat_rate', 'flat_shipping_enabled' => true, 'flat_shipping_charge' => 80, 'free_shipping_enabled' => true, 'free_shipping_threshold' => 999]);
        $before = InventoryMovement::count();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['postal_code' => '110001', 'shipping_charge' => 1, 'weight' => 1])->assertOk()->assertJsonPath('data.selected_quote.shipping_charge', '0.00')->assertJsonPath('data.payable_before_order', '1200.00');
        $coupon = Coupon::factory()->create(['code' => 'LESS300', 'discount_type' => 'fixed', 'discount_value' => 300]);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/coupon', ['code' => 'LESS300']);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['postal_code' => '110001'])->assertJsonPath('data.selected_quote.shipping_charge', '80.00')->assertJsonPath('data.payable_before_order', '980.00');
        $this->assertSame($before, InventoryMovement::count());
    }

    public function test_address_idor_is_blocked(): void
    {
        $b = $this->book();
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $address = CustomerAddress::factory()->create(['user_id' => $owner->id]);
        ShippingSetting::current()->update(['shipping_enabled' => true, 'mode' => 'flat_rate']);
        $this->cart($b, $attacker);
        $this->postJson('/api/v1/checkout/shipping-options', ['address_id' => $address->id])->assertNotFound();
    }

    public function test_shiprocket_mock_auth_rate_normalization_and_package_weight(): void
    {
        $b = $this->book(800, 750);
        $token = $this->cart($b);
        $p = ShippingProvider::where('code', 'SHIPROCKET')->first();
        $p->update(['configuration' => ['email' => 'api@example.com', 'password' => 'secret'], 'connection_status' => 'connected', 'is_enabled' => true, 'is_default' => true]);
        ShippingSetting::current()->update(['shipping_enabled' => true, 'mode' => 'live_provider', 'default_provider_id' => $p->id]);
        ShippingPickupLocation::create(['name' => 'Main', 'contact_name' => 'E4', 'phone' => '9876543210', 'address_line1' => 'Origin', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'is_default' => true]);
        Http::fake(['*/auth/login' => Http::response(['token' => 'mock-token']), '*/courier/serviceability/*' => Http::response(['data' => ['available_courier_companies' => [['courier_company_id' => 5, 'courier_name' => 'Mock Express', 'rate' => 75, 'cod' => 1, 'etd' => '2026-10-08']]]])]);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['postal_code' => '560001'])->assertOk()->assertJsonPath('data.selected_quote.provider', 'SHIPROCKET')->assertJsonPath('data.selected_quote.shipping_charge', '75.00');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'serviceability') && $r['weight'] === '0.750');
    }

    public function test_nimbuspost_adapter_mock_and_test_connection_sanitizes_failure(): void
    {
        $p = ShippingProvider::where('code', 'NIMBUSPOST')->first();
        $p->update(['configuration' => ['email' => 'api@example.com', 'password' => 'secret']]);
        Http::fake(['*/users/login' => Http::response(['data' => ['token' => 'nimbus-token']]), '*/courier/serviceability' => Http::response(['data' => [['courier_id' => 7, 'courier_name' => 'Nimbus Mock', 'freight_charge' => 65, 'cod' => true]]])]);
        $adapter = app(NimbusPostShippingProvider::class);
        $this->assertTrue($adapter->testConnection($p)['connected']);
        $rates = $adapter->rates($p, ['origin_postal_code' => '110001', 'destination_postal_code' => '560001', 'weight_grams' => 500, 'weight_kg' => '0.500', 'declared_value' => '500.00', 'cod' => false]);
        $this->assertSame('NIMBUSPOST', $rates[0]['provider']);
    }

    public function test_customer_cannot_access_admin_shipping_and_secrets_never_appear(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer)->getJson('/api/v1/admin/shipping/providers')->assertForbidden();
        $this->assertStringNotContainsString('top-secret',json_encode(ShippingProvider::all()->toArray()));
    }
}
