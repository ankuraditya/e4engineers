<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\InvoiceIssued;
use App\Jobs\CreateShipment;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Order;
use App\Models\ShippingSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function issueForOrder(Order $order): Invoice
    {
        $invoice = DB::transaction(function () use ($order): Invoice {
            $order = Order::whereKey($order->id)->lockForUpdate()->with(['items', 'shippingAddress', 'paymentAttempts.transactions'])->firstOrFail();
            if ($existing = Invoice::where('order_id', $order->id)->first()) {
                return $existing;
            }
            $this->ensureEligible($order);
            $settings = InvoiceSetting::current();
            [$number, $period] = $this->nextNumber($settings);
            $address = $this->addressSnapshot($order);
            $transaction = $order->paymentAttempts->flatMap->transactions->where('status', 'succeeded')->sortByDesc('id')->first();
            $invoice = Invoice::create([
                'order_id' => $order->id, 'invoice_number' => $number,
                'invoice_type' => filled($settings->gstin) ? 'tax_invoice' : 'invoice', 'status' => 'issued',
                'issued_at' => now(), 'financial_year' => $period, 'currency' => $order->currency,
                'seller_snapshot' => $this->sellerSnapshot($settings),
                'customer_snapshot' => ['name' => $address['name'], 'email' => $address['email'], 'mobile' => $address['mobile']],
                'billing_address_snapshot' => $address, 'shipping_address_snapshot' => $address,
                'subtotal' => $order->subtotal, 'discount_amount' => $order->discount_total,
                'store_credit_amount' => $order->store_credit_total,
                'shipping_amount' => $order->shipping_total, 'cod_charge' => $order->cod_charge,
                'taxable_amount' => null, 'total_tax' => $order->tax_total,
                'grand_total' => $order->grand_total, 'payment_method' => $order->payment_method,
                'payment_status_snapshot' => $order->payment_status->value,
                'payment_provider' => strtoupper($order->payment_method),
                'payment_reference_snapshot' => $transaction?->provider_transaction_id,
                'presentation_snapshot' => $settings->only(['footer_text', 'terms', 'declaration', 'authorized_signatory_name', 'authorized_signatory_designation', 'signature_path', 'show_gst_columns', 'show_hsn', 'show_discount', 'show_shipping', 'show_payment_method', 'show_payment_reference']),
            ]);
            foreach ($order->items as $item) {
                $snapshot = $item->product_snapshot ?? [];
                $invoice->items()->create([
                    'order_item_id' => $item->id, 'title' => $item->title, 'sku' => $item->sku,
                    'isbn' => $snapshot['isbn'] ?? null, 'hsn_code' => $snapshot['hsn_code'] ?? null,
                    'quantity' => $item->quantity, 'mrp' => $snapshot['mrp'] ?? null,
                    'unit_price' => $item->unit_price, 'discount_amount' => $item->discount_total,
                    'line_total' => $item->line_total,
                ]);
            }

            return $invoice->load(['items', 'order']);
        }, 3);

        if (! $invoice->pdf_path) {
            $this->generatePdf($invoice);
            InvoiceIssued::dispatch($invoice->refresh());
        }
        if (ShippingSetting::current()->automatic_shipment_creation && ! $invoice->order->shipment()->exists()) {
            CreateShipment::dispatch($invoice->order_id)->afterCommit();
        }

        return $invoice->refresh()->load(['items', 'order']);
    }

    public function generatePdf(Invoice $invoice): Invoice
    {
        $invoice->loadMissing(['items', 'order']);
        $path = 'invoices/'.$invoice->id.'/invoice.pdf';
        Storage::disk('local')->put($path, Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4')->output());
        $invoice->update(['pdf_path' => $path, 'pdf_generated_at' => now()]);

        return $invoice->refresh();
    }

    public function regeneratePdfFromImmutableInvoice(Invoice $invoice): Invoice
    {
        return $this->generatePdf($invoice);
    }

    public function voidInvoice(Invoice $invoice, string $reason, int $userId): Invoice
    {
        if ($invoice->status === 'void') {
            return $invoice;
        }
        $invoice->update(['status' => 'void', 'voided_at' => now(), 'void_reason' => $reason, 'voided_by' => $userId]);

        return $this->generatePdf($invoice);
    }

    private function ensureEligible(Order $order): void
    {
        $eligible = $order->payment_method === 'cod'
            ? $order->status === OrderStatus::Confirmed
            : $order->status === OrderStatus::Confirmed && $order->payment_status === PaymentStatus::Paid;
        if (! $eligible) {
            throw ValidationException::withMessages(['order' => ['Order is not eligible for invoice issuance.']]);
        }
    }

    private function nextNumber(InvoiceSetting $settings): array
    {
        $now = now();
        $financialYear = $now->month >= 4 ? $now->year.'-'.substr((string) ($now->year + 1), -2) : ($now->year - 1).'-'.substr((string) $now->year, -2);
        $period = $settings->period_strategy === 'calendar_year' ? (string) $now->year : ($settings->period_strategy === 'none' ? '' : $financialYear);
        $key = match ($settings->sequence_reset) {
            'yearly' => 'Y-'.$now->year, 'financial_year' => 'FY-'.$financialYear, default => 'GLOBAL'
        };
        DB::table('invoice_sequences')->insertOrIgnore(['sequence_key' => $key, 'current_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $sequence = DB::table('invoice_sequences')->where('sequence_key', $key)->lockForUpdate()->first();
        $next = $sequence->current_number + 1;
        DB::table('invoice_sequences')->where('id', $sequence->id)->update(['current_number' => $next, 'updated_at' => now()]);
        $number = str_replace(['{PREFIX}', '{PERIOD}', '{SEQUENCE}'], [$settings->invoice_prefix ?: 'E4E/INV', $period, str_pad((string) $next, $settings->sequence_padding ?: 6, '0', STR_PAD_LEFT)], $settings->number_format ?: '{PREFIX}/{PERIOD}/{SEQUENCE}');
        $number = preg_replace('#/+#', '/', trim($number, '/'));

        return [$number, $period ?: null];
    }

    private function sellerSnapshot(InvoiceSetting $settings): array
    {
        return $settings->only(['business_name', 'display_name', 'gstin', 'pan', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'phone', 'email', 'logo_path']);
    }

    private function addressSnapshot(Order $order): array
    {
        $address = $order->shippingAddress;

        return ['name' => $address->name, 'email' => $address->email, 'mobile' => $address->mobile, 'address_line_1' => $address->address_line1, 'address_line_2' => $address->address_line2, 'landmark' => $address->landmark, 'city' => $address->city, 'state' => $address->state, 'postal_code' => $address->postal_code, 'country' => $address->country];
    }
}
