<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Book;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use App\Models\PaymentSetting;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use App\Services\Payment\CashfreeGateway;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PayUGateway;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\PaymentProviderSeeder;
use Database\Seeders\ShippingProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, PaymentProviderSeeder::class, ShippingProviderSeeder::class]);
    }

    public function test_credentials_are_encrypted_masked_and_provider_requires_connection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $p = PaymentProvider::where('code', 'RAZORPAY')->first();
        $this->actingAs($admin)->putJson("/api/v1/admin/payments/providers/$p->id/credentials", ['credentials' => ['key_id' => 'rzp_test_x', 'key_secret' => 'secret', 'webhook_secret' => 'hook']])->assertOk()->assertJsonPath('data.configuration.key_secret_configured', true)->assertJsonMissing(['key_secret' => 'secret']);
        $this->assertStringNotContainsString('secret', DB::table('payment_providers')->where('id', $p->id)->value('configuration'));
        $this->patchJson("/api/v1/admin/payments/providers/$p->id/toggle", ['enabled' => true])->assertUnprocessable();
    }

    public function test_online_checkout_reserves_then_verified_razorpay_payment_finalizes_once(): void
    {
        [$book,$token,$quote] = $this->fixture();
        $p = PaymentProvider::where('code', 'RAZORPAY')->first();
        $p->update(['configuration' => ['key_id' => 'rzp_test_x', 'key_secret' => 'secret', 'webhook_secret' => 'hook'], 'connection_status' => 'connected', 'is_enabled' => true, 'is_default' => true]);
        PaymentSetting::current()->update(['online_payments_enabled' => true]);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_gateway_1', 'amount' => 108000, 'currency' => 'INR'])]);
        $placed = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote, 'razorpay'))->assertCreated()->assertJsonPath('data.order.status', 'payment_pending')->assertJsonPath('data.payment.provider', 'RAZORPAY');
        $order = Order::first();
        $attempt = PaymentAttempt::first();
        $this->assertSame(5, $book->inventory->fresh()->stock_quantity);
        $this->assertSame(1, $book->inventory->fresh()->reserved_quantity);
        $signature = hash_hmac('sha256', 'order_gateway_1|pay_1', 'secret');
        $headers = ['X-Order-Access-Token' => $placed->json('data.order_access_token')];
        $this->withHeaders($headers)->postJson("/api/v1/orders/$order->order_number/payments/$attempt->id/verify", ['razorpay_payment_id' => 'pay_1', 'razorpay_signature' => $signature])->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/orders/$order->order_number/payments/$attempt->id/verify", ['razorpay_payment_id' => 'pay_1', 'razorpay_signature' => $signature])->assertOk();
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(4, $book->inventory->fresh()->stock_quantity);
        $this->assertSame(0, $book->inventory->fresh()->reserved_quantity);
        $this->assertSame(1, DB::table('payment_transactions')->count());
    }

    public function test_invalid_razorpay_signature_and_amount_tampering_are_rejected(): void
    {
        [$book,$token,$quote] = $this->fixture();
        $p = PaymentProvider::where('code', 'RAZORPAY')->first();
        $p->update(['configuration' => ['key_id' => 'id', 'key_secret' => 'secret', 'webhook_secret' => 'hook'], 'connection_status' => 'connected', 'is_enabled' => true]);
        PaymentSetting::current()->update(['online_payments_enabled' => true]);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_x', 'amount' => 108000, 'currency' => 'INR'])]);
        $placed = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote, 'razorpay') + ['total' => 1]);
        $attempt = PaymentAttempt::first();
        $this->withHeader('X-Order-Access-Token', $placed->json('data.order_access_token'))->postJson('/api/v1/orders/'.Order::first()->order_number."/payments/$attempt->id/verify", ['razorpay_payment_id' => 'pay_x', 'razorpay_signature' => 'bad'])->assertUnprocessable();
        $this->assertSame(1, $book->inventory->fresh()->reserved_quantity);
    }

    public function test_payu_and_cashfree_signature_algorithms(): void
    {
        $payu = PaymentProvider::where('code', 'PAYU')->first();
        $payu->update(['configuration' => ['merchant_key' => 'key', 'merchant_salt' => 'salt']]);
        $order = Order::factory()->create(['status' => OrderStatus::PaymentPending, 'payment_status' => PaymentStatus::Pending, 'shipping_status' => ShippingStatus::NotCreated, 'payment_method' => 'payu']);
        $attempt = PaymentAttempt::create(['id' => (string) Str::uuid(), 'order_id' => $order->id, 'payment_provider_id' => $payu->id, 'environment' => 'test', 'status' => 'pending', 'amount' => $order->grand_total, 'currency' => 'INR', 'idempotency_key' => (string) Str::uuid(), 'expires_at' => now()->addMinutes(20)]);
        $action = app(PayUGateway::class)->initiate($payu, $attempt->load('order'), ['name' => 'Test', 'email' => 'test@example.com']);
        $this->assertSame('form_post', $action['action']['type']);
        $response = [
            'status' => 'success', 'email' => 'test@example.com', 'firstname' => 'Test',
            'productinfo' => $order->order_number, 'amount' => (string) $attempt->amount,
            'txnid' => $attempt->id, 'mihpayid' => 'payu_payment_1',
        ];
        $sign = static fn (array $values): string => hash('sha512', implode('|', [
            'salt', $values['status'], '', '', '', '', '', '', '', '', '',
            $values['email'], $values['firstname'], $values['productinfo'],
            $values['amount'], $values['txnid'], 'key',
        ]));
        $response['hash'] = $sign($response);
        $this->assertTrue(app(PayUGateway::class)->verifyReturn($payu, $attempt, $response)['valid']);
        $response['amount'] = '1.00';
        $response['hash'] = $sign($response);
        $this->assertFalse(app(PayUGateway::class)->verifyReturn($payu, $attempt, $response)['valid']);
        $cash = PaymentProvider::where('code', 'CASHFREE')->first();
        $cash->update(['configuration' => ['client_id' => 'id', 'client_secret' => 'secret']]);
        $raw = '{"type":"PAYMENT_SUCCESS_WEBHOOK"}';
        $ts = '123';
        $sig = base64_encode(hash_hmac('sha256', $ts.$raw, 'secret', true));
        $this->assertTrue(app(CashfreeGateway::class)->verifyWebhook($cash, $raw, ['x-webhook-timestamp' => [$ts], 'x-webhook-signature' => [$sig]])['valid']);
    }

    public function test_signed_success_webhook_without_amount_cannot_mark_an_order_paid(): void
    {
        $provider = PaymentProvider::where('code', 'RAZORPAY')->first();
        $provider->update(['configuration' => ['key_id' => 'id', 'key_secret' => 'secret', 'webhook_secret' => 'hook']]);
        $order = Order::factory()->create(['payment_status' => PaymentStatus::Pending]);
        PaymentAttempt::create([
            'id' => (string) Str::uuid(), 'order_id' => $order->id,
            'payment_provider_id' => $provider->id, 'environment' => 'test',
            'status' => 'pending', 'amount' => $order->grand_total, 'currency' => 'INR',
            'idempotency_key' => (string) Str::uuid(), 'provider_order_id' => 'order_gateway_1',
            'expires_at' => now()->addMinutes(20),
        ]);
        $payload = ['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => [
            'id' => 'pay_1', 'order_id' => 'order_gateway_1', 'status' => 'captured',
        ]]]];
        $raw = json_encode($payload);
        $this->call('POST', '/api/v1/payments/webhooks/razorpay', [], [], [], [
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $raw, 'hook'),
            'CONTENT_TYPE' => 'application/json',
        ], $raw)->assertStatus(422);
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    public function test_failed_attempt_releases_inventory_and_retry_can_reserve_again(): void
    {
        [$book,$token,$quote] = $this->fixture();
        $p = PaymentProvider::where('code', 'RAZORPAY')->first();
        $p->update(['configuration' => ['key_id' => 'id', 'key_secret' => 'secret', 'webhook_secret' => 'hook'], 'connection_status' => 'connected', 'is_enabled' => true]);
        PaymentSetting::current()->update(['online_payments_enabled' => true]);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_x', 'amount' => 108000, 'currency' => 'INR'])]);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote, 'razorpay'));
        $attempt = PaymentAttempt::first();
        app(PaymentService::class)->fail($attempt, ['reason' => 'declined']);
        $this->assertSame(0, $book->inventory->fresh()->reserved_quantity);
        app(PaymentService::class)->initiate(Order::first(), (string) Str::uuid(), 'RAZORPAY');
        $this->assertSame(1, $book->inventory->fresh()->reserved_quantity);
    }

    public function test_scan_payment_requires_private_proof_and_admin_approval(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $provider = PaymentProvider::where('code', 'SCANPAY')->firstOrFail();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg==');

        $this->actingAs($admin)->post("/api/v1/admin/payments/providers/{$provider->id}/scan-code", [
            'upi_id' => 'store@upi', 'payee_name' => 'E4ENGINEERS',
            'qr' => UploadedFile::fake()->createWithContent('qr.png', $png),
        ])->assertOk();
        $this->patchJson("/api/v1/admin/payments/providers/{$provider->id}/toggle", ['enabled' => true])->assertOk();
        $this->getJson('/api/v1/payments/methods')->assertJsonPath('data.scanpay.enabled', true);

        [$book, $token, $quote] = $this->fixture();
        $this->app['auth']->guard('web')->logout();
        $placed = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote, 'scanpay'))->assertCreated()->assertJsonPath('data.payment', null);
        $order = Order::firstOrFail();
        $attempt = PaymentAttempt::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(1, $book->inventory->fresh()->reserved_quantity);
        $this->actingAs($admin)->postJson("/api/v1/admin/payments/attempts/{$attempt->id}/review", ['decision' => 'approve'])->assertUnprocessable();
        $proofUrl = "/api/v1/orders/{$order->order_number}/payments/scan-proof";
        $this->actingAs(User::factory()->create());
        $this->post($proofUrl, ['screenshot' => UploadedFile::fake()->createWithContent('proof.png', $png)])->assertNotFound();
        $this->withHeader('X-Order-Access-Token', $placed->json('data.order_access_token'))->post($proofUrl, [
            'screenshot' => UploadedFile::fake()->createWithContent('proof.png', $png),
            'reference' => 'UPI12345',
        ])->assertOk()->assertJsonPath('data.status', 'pending_review');
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        Storage::disk('private')->assertExists($attempt->fresh()->proof_path);
        $this->get("/api/v1/admin/payments/attempts/{$attempt->id}/proof")->assertForbidden();

        $this->actingAs($admin)->get("/api/v1/admin/payments/attempts/{$attempt->id}/proof")->assertOk();
        $this->actingAs($admin)->postJson("/api/v1/admin/payments/attempts/{$attempt->id}/review", ['decision' => 'approve'])->assertOk();
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(0, $book->inventory->fresh()->reserved_quantity);
        $this->assertSame(4, $book->inventory->fresh()->stock_quantity);
    }

    public function test_rejecting_scan_payment_proof_releases_reserved_stock(): void
    {
        Storage::fake('private');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg==');
        Storage::disk('private')->put('payments/qr/test.png', $png);
        PaymentProvider::where('code', 'SCANPAY')->firstOrFail()->update([
            'configuration' => ['upi_id' => 'store@upi', 'payee_name' => 'E4ENGINEERS', 'qr_path' => 'payments/qr/test.png'],
            'connection_status' => 'connected', 'is_enabled' => true,
        ]);
        [$book, $token, $quote] = $this->fixture();
        $placed = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote, 'scanpay'))->assertCreated();
        $order = Order::firstOrFail();
        $attempt = PaymentAttempt::where('order_id', $order->id)->firstOrFail();
        $this->withHeader('X-Order-Access-Token', $placed->json('data.order_access_token'))->post("/api/v1/orders/{$order->order_number}/payments/scan-proof", [
            'screenshot' => UploadedFile::fake()->createWithContent('proof.png', $png),
        ])->assertOk();
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $this->actingAs($admin)->postJson("/api/v1/admin/payments/attempts/{$attempt->id}/review", ['decision' => 'reject', 'note' => 'Payment not found in bank statement'])->assertOk();
        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);
        $this->assertSame(5, $book->inventory->fresh()->stock_quantity);
        $this->assertSame(0, $book->inventory->fresh()->reserved_quantity);
    }

    public function test_cancelling_pending_scan_order_releases_reservation_without_creating_stock(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('payments/qr/test.png', 'qr');
        PaymentProvider::where('code', 'SCANPAY')->firstOrFail()->update([
            'configuration' => ['upi_id' => 'store@upi', 'payee_name' => 'E4ENGINEERS', 'qr_path' => 'payments/qr/test.png'],
            'is_enabled' => true,
        ]);
        [$book, $token, $quote] = $this->fixture();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout/place-order', $this->payload($quote, 'scanpay'))->assertCreated();
        $admin = User::factory()->create();
        app(OrderStatusService::class)->transition(Order::firstOrFail(), OrderStatus::Cancelled, $admin->id);
        $this->assertSame(5, $book->inventory->fresh()->stock_quantity);
        $this->assertSame(0, $book->inventory->fresh()->reserved_quantity);
    }

    private function fixture(): array
    {
        $b = Book::factory()->create(['selling_price' => 1000, 'mrp' => 1200]);
        app(InventoryService::class)->increase($b, 5, InventoryMovementType::InitialStock, 'Fixture');
        ShippingSetting::current()->update(['shipping_enabled' => true, 'mode' => 'flat_rate', 'flat_shipping_enabled' => true, 'flat_shipping_charge' => 80]);
        $token = Str::random(64);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $b->id, 'quantity' => 1]);
        $q = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/shipping/quote', ['postal_code' => '110001', 'payment_mode' => 'prepaid'])->json('data.selected_quote.quote_id');

        return [$b, $token, $q];
    }

    private function payload(string $q, string $method): array
    {
        return ['contact' => ['name' => 'Guest', 'email' => 'guest@example.com', 'mobile' => '9876543210'], 'shipping_address' => ['type' => 'home', 'full_name' => 'Guest', 'mobile' => '9876543210', 'address_line_1' => 'Road', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country_code' => 'IN'], 'shipping_quote_id' => $q, 'payment_method' => $method, 'idempotency_key' => (string) Str::uuid()];
    }
}
