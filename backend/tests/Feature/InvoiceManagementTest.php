<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\Payment\PaymentService;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\PaymentProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, PaymentProviderSeeder::class]);
        Storage::fake('local');
    }

    public function test_cod_invoice_is_idempotent_numbered_and_generated_privately(): void
    {
        $order = $this->order();
        $service = app(InvoiceService::class);
        $first = $service->issueForOrder($order);
        $second = $service->issueForOrder($order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('E4E/INV/'.now()->format('Y').'-'.substr((string) (now()->year + 1), -2).'/000001', $first->invoice_number);
        $this->assertSame(1, Invoice::count());
        Storage::disk('local')->assertExists($first->pdf_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($first->pdf_path));
    }

    public function test_invoice_snapshot_and_regenerated_pdf_ignore_later_order_and_setting_changes(): void
    {
        InvoiceSetting::current()->update(['business_name' => 'Original Seller', 'address_line_1' => 'Original Address']);
        $invoice = app(InvoiceService::class)->issueForOrder($this->order());
        $snapshot = $invoice->only(['seller_snapshot', 'customer_snapshot', 'shipping_address_snapshot', 'subtotal', 'discount_amount', 'shipping_amount', 'grand_total']);
        InvoiceSetting::current()->update(['business_name' => 'Changed Seller', 'address_line_1' => 'Changed Address']);
        $invoice->order->update(['subtotal' => 1, 'grand_total' => 1]);
        app(InvoiceService::class)->regeneratePdfFromImmutableInvoice($invoice);

        $this->assertSame($snapshot, $invoice->fresh()->only(array_keys($snapshot)));
    }

    public function test_pending_online_order_is_ineligible(): void
    {
        $order = $this->order(['payment_method' => 'razorpay', 'status' => OrderStatus::PaymentPending, 'payment_status' => PaymentStatus::Pending]);
        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->issueForOrder($order);
    }

    public function test_each_verified_online_provider_issues_one_invoice(): void
    {
        foreach (['RAZORPAY', 'PAYU', 'CASHFREE'] as $code) {
            $order = Order::factory()->create(['status' => OrderStatus::PaymentPending, 'payment_status' => PaymentStatus::Pending, 'payment_method' => strtolower($code), 'subtotal' => 100, 'grand_total' => 100]);
            $order->shippingAddress()->create(['type' => 'shipping', 'name' => 'Online Customer', 'email' => 'online@example.com', 'mobile' => '9876543210', 'address_line1' => 'Payment Road', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country' => 'IN']);
            $attempt = PaymentAttempt::create(['id' => (string) Str::uuid(), 'order_id' => $order->id, 'payment_provider_id' => PaymentProvider::where('code', $code)->value('id'), 'environment' => 'test', 'status' => 'pending', 'amount' => 100, 'currency' => 'INR', 'idempotency_key' => (string) Str::uuid(), 'expires_at' => now()->addMinutes(20)]);
            app(PaymentService::class)->succeed($attempt, 'verified-'.$code, []);
            $this->assertDatabaseHas('invoices', ['order_id' => $order->id, 'payment_provider' => $code]);
        }
        $this->assertSame(3, Invoice::count());
    }

    public function test_sequence_is_unique_across_orders(): void
    {
        $service = app(InvoiceService::class);
        $numbers = [$service->issueForOrder($this->order())->invoice_number, $service->issueForOrder($this->order())->invoice_number];
        $this->assertCount(2, array_unique($numbers));
        $this->assertSame(2, \DB::table('invoice_sequences')->value('current_number'));
    }

    public function test_customer_download_is_private_and_blocks_idor(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $order = $this->order(['user_id' => $owner->id]);
        app(InvoiceService::class)->issueForOrder($order);
        $this->actingAs($attacker)->get('/api/v1/orders/'.$order->order_number.'/invoice/download')->assertNotFound();
        $this->actingAs($owner)->get('/api/v1/orders/'.$order->order_number.'/invoice/download')->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_void_keeps_financial_snapshot_and_marks_pdf(): void
    {
        $admin = User::factory()->create();
        $invoice = app(InvoiceService::class)->issueForOrder($this->order());
        $total = $invoice->grand_total;
        app(InvoiceService::class)->voidInvoice($invoice, 'Administrative correction', $admin->id);
        $this->assertSame('void', $invoice->fresh()->status);
        $this->assertSame($total, $invoice->fresh()->grand_total);
    }

    private function order(array $attributes = []): Order
    {
        $order = Order::factory()->create($attributes + ['status' => OrderStatus::Confirmed, 'payment_status' => PaymentStatus::CodPending, 'payment_method' => 'cod', 'subtotal' => 1000, 'discount_total' => 100, 'shipping_total' => 80, 'cod_charge' => 20, 'tax_total' => 0, 'grand_total' => 1000]);
        $order->shippingAddress()->create(['type' => 'shipping', 'name' => 'Test Engineer', 'email' => 'engineer@example.com', 'mobile' => '9876543210', 'address_line1' => 'Historical Road', 'city' => 'Delhi', 'state' => 'Delhi', 'postal_code' => '110001', 'country' => 'IN']);
        $order->items()->create(['sku' => 'E4-001', 'title' => 'Engineering Book', 'quantity' => 2, 'unit_price' => 500, 'discount_total' => 100, 'line_total' => 900, 'product_snapshot' => ['mrp' => 600]]);

        return $order;
    }
}
