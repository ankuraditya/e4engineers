<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('E4ENGINEERS');
            $table->string('display_name')->default('E4ENGINEERS');
            $table->string('gstin')->nullable();
            $table->string('pan')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->string('country')->default('India');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('authorized_signatory_name')->nullable();
            $table->string('authorized_signatory_designation')->nullable();
            $table->string('invoice_prefix')->default('E4E/INV');
            $table->string('number_format')->default('{PREFIX}/{PERIOD}/{SEQUENCE}');
            $table->string('period_strategy')->default('financial_year');
            $table->string('sequence_reset')->default('financial_year');
            $table->unsignedTinyInteger('sequence_padding')->default(6);
            $table->text('footer_text')->nullable();
            $table->text('terms')->nullable();
            $table->text('declaration')->nullable();
            $table->boolean('show_gst_columns')->default(false);
            $table->boolean('show_hsn')->default(false);
            $table->boolean('show_discount')->default(true);
            $table->boolean('show_shipping')->default(true);
            $table->boolean('show_payment_method')->default(true);
            $table->boolean('show_payment_reference')->default(true);
            $table->timestamps();
        });
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_key')->unique();
            $table->unsignedBigInteger('current_number')->default(0);
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('invoice_type')->default('invoice');
            $table->string('status')->default('issued')->index();
            $table->timestamp('issued_at');
            $table->string('financial_year')->nullable();
            $table->string('currency', 3);
            $table->json('seller_snapshot');
            $table->json('customer_snapshot');
            $table->json('billing_address_snapshot');
            $table->json('shipping_address_snapshot');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('cod_charge', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->nullable();
            $table->decimal('cgst_amount', 12, 2)->nullable();
            $table->decimal('sgst_amount', 12, 2)->nullable();
            $table->decimal('igst_amount', 12, 2)->nullable();
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('payment_method');
            $table->string('payment_status_snapshot');
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference_snapshot')->nullable();
            $table->json('presentation_snapshot');
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('sku')->nullable();
            $table->string('isbn')->nullable();
            $table->string('hsn_code')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('mrp', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_value', 12, 2)->nullable();
            $table->decimal('cgst_rate', 7, 3)->nullable();
            $table->decimal('cgst_amount', 12, 2)->nullable();
            $table->decimal('sgst_rate', 7, 3)->nullable();
            $table->decimal('sgst_amount', 12, 2)->nullable();
            $table->decimal('igst_rate', 7, 3)->nullable();
            $table->decimal('igst_amount', 12, 2)->nullable();
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
        Schema::dropIfExists('invoice_settings');
    }
};
