<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingPickupLocation;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Services\Shipping\ShipmentService;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\ShippingProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ShipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, ShippingProviderSeeder::class]);
        ShippingPickupLocation::create(['name' => 'Main', 'contact_name' => 'Dispatch', 'phone' => '9876543210', 'address_line1' => 'Warehouse Road', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country' => 'IN', 'is_default' => true, 'is_active' => true]);
    }

    public function test_shiprocket_booking_is_idempotent_and_uses_server_order_values(): void
    {
        [$order, $provider] = $this->fixture('SHIPROCKET');
        Http::fake(['*/orders/create/adhoc' => Http::response(['order_id' => 44, 'shipment_id' => 55])]);
        $service = app(ShipmentService::class);
        $first = $service->create($order, 'SHIPROCKET');
        $second = $service->create($order, 'SHIPROCKET');
        $this->assertSame($first->id, $second->id);
        $this->assertSame('COD', $first->payment_mode);
        $this->assertEquals(579, $first->cod_amount);
        $this->assertSame(1, Shipment::count());
        Http::assertSent(fn ($request) => str_contains($request->url(), '/orders/create/adhoc') && $request['order_id'] === $order->order_number && $request['cod_amount'] === '579.00');
    }

    public function test_nimbus_booking_and_tracking_status_precedence(): void
    {
        [$order] = $this->fixture('NIMBUSPOST');
        Http::fake(['*/shipments' => Http::response(['data' => ['shipment_id' => 'N-1', 'awb' => 'AWB-1', 'courier_name' => 'Nimbus Express']])]);
        $shipment = app(ShipmentService::class)->create($order, 'NIMBUSPOST');
        $service = app(ShipmentService::class);
        $service->handleTracking($shipment, 'DELIVERED', 'Delivered', now(), 'Delhi', 'webhook', 'event-2');
        $service->handleTracking($shipment, 'IN TRANSIT', 'Old transit scan', now()->subDay(), 'Jaipur', 'webhook', 'event-1');
        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()->status);
        $this->assertCount(3, $shipment->trackingEvents);
    }

    public function test_webhook_authentication_deduplication_and_rto_mapping(): void
    {
        [$order, $provider] = $this->fixture('SHIPROCKET', ['webhook_secret' => 'hook-secret']);
        Http::fake(['*/orders/create/adhoc' => Http::response(['order_id' => 44, 'shipment_id' => 55])]);
        $shipment = app(ShipmentService::class)->create($order, 'SHIPROCKET');
        $shipment->update(['awb_number' => 'AWB-9']);
        $payload = ['id' => 'carrier-event-1', 'awb' => 'AWB-9', 'current_status' => 'RTO IN TRANSIT', 'current_timestamp' => now()->toIso8601String()];
        $this->postJson('/api/v1/shipping/webhooks/shiprocket', $payload)->assertUnauthorized();
        $this->withHeader('x-api-key', 'hook-secret')->postJson('/api/v1/shipping/webhooks/shiprocket', $payload)->assertOk();
        $this->withHeader('x-api-key', 'hook-secret')->postJson('/api/v1/shipping/webhooks/shiprocket', $payload)->assertOk();
        $this->assertSame(ShipmentStatus::RtoInTransit, $shipment->fresh()->status);
        $this->assertDatabaseCount('shipment_webhook_events', 1);
    }

    public function test_tracking_blocks_idor_and_allows_owner(): void
    {
        [$order] = $this->fixture('SHIPROCKET');
        Http::fake(['*/orders/create/adhoc' => Http::response(['order_id' => 44, 'shipment_id' => 55])]);
        app(ShipmentService::class)->create($order, 'SHIPROCKET');
        $this->actingAs(User::factory()->create())->getJson('/api/v1/orders/'.$order->order_number.'/tracking')->assertNotFound();
        $this->actingAs($order->user)->getJson('/api/v1/orders/'.$order->order_number.'/tracking')->assertOk()->assertJsonPath('data.status', 'booked');
    }

    public function test_cancel_does_not_change_order_financials(): void
    {
        [$order] = $this->fixture('SHIPROCKET');
        Http::fake(['*/orders/create/adhoc' => Http::response(['order_id' => 44, 'shipment_id' => 55]), '*/orders/cancel' => Http::response(['status' => 1])]);
        $shipment = app(ShipmentService::class)->create($order, 'SHIPROCKET');
        $total = $order->grand_total;
        app(ShipmentService::class)->cancel($shipment);
        $this->assertSame(ShipmentStatus::Cancelled, $shipment->fresh()->status);
        $this->assertSame($total, $order->fresh()->grand_total);
    }

    public function test_shipprime_booking_tracking_and_webhook_use_awb_and_bearer_secret(): void
    {
        [$order, $provider] = $this->fixture('SHIPPRIME', ['api_token' => 'test-token', 'webhook_secret' => 'hook-secret']);
        Http::fake([
            '*/v1/forward' => Http::response(['status' => 'SUCCESS', 'awb' => 'SP12345', 'courier' => 'DELHIVERY', 'orderId' => 101, 'labelUrl' => 'https://cdn.shipprime.live/labels/101.pdf']),
            '*/v1/forward/track*' => Http::response(['status' => 'SUCCESS', 'results' => [['awb' => 'SP12345', 'currentStatus' => 'SHIPPED', 'history' => [['status' => 'PICKED UP', 'statusDate' => now()->subHour()->toDateTimeString()]]]]]),
            '*/v1/forward/SP12345/cancel' => Http::response(['status' => 'SUCCESS']),
        ]);

        $shipment = app(ShipmentService::class)->create($order, 'SHIPPRIME');
        $this->assertSame('SP12345', $shipment->awb_number);
        $this->assertSame('https://cdn.shipprime.live/labels/101.pdf', $shipment->provider_label_url);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.shipprime.live/v1/forward'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['pickupAddress']['phone'] === '9876543210'
            && $request['deliveryAddress']['pincode'] === '110001'
            && $request['collectibleAmount'] === 579.0);

        app(ShipmentService::class)->refreshTracking($shipment->load('provider'));
        $this->assertSame(ShipmentStatus::InTransit, $shipment->fresh()->status);

        $payload = ['id' => 'shipprime-event-1', 'awb' => 'SP12345', 'currentStatus' => 'DELIVERED', 'statusDate' => now()->toIso8601String()];
        $this->postJson('/api/v1/shipping/webhooks/shipprime', $payload)->assertUnauthorized();
        $this->withToken('hook-secret')->postJson('/api/v1/shipping/webhooks/shipprime', $payload)->assertOk();
        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()->status);
    }

    private function fixture(string $code, array $configuration = []): array
    {
        $provider = ShippingProvider::where('code', $code)->firstOrFail();
        $provider->update(['configuration' => $configuration + ['email' => 'api@example.com', 'password' => 'secret'], 'connection_status' => 'connected', 'is_enabled' => true]);
        Cache::put('shipping:'.strtolower($code).':'.$provider->id.':auth-token', 'test-token');
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed, 'payment_status' => PaymentStatus::CodPending, 'payment_method' => 'cod', 'grand_total' => 579]);
        $order->shippingAddress()->create(['type' => 'shipping', 'name' => 'Test Engineer', 'email' => 'engineer@example.com', 'mobile' => '9876543210', 'address_line1' => 'Delivery Road', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country' => 'IN']);
        $order->items()->create(['sku' => 'E4-001', 'title' => 'Engineering Book', 'quantity' => 1, 'unit_price' => 499, 'discount_total' => 0, 'line_total' => 499, 'product_snapshot' => []]);

        return [$order, $provider];
    }
}
