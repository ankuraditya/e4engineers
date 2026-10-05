<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('password_setup_required')->default(false)->after('status');
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable()->index();
            $table->string('guest_mobile', 10)->nullable()->index();
            $table->string('status', 30)->index();
            $table->string('payment_status', 30)->index();
            $table->string('shipping_status', 30)->index();
            $table->string('payment_method', 20);
            $table->string('currency', 3)->default('INR');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('cod_charge', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->json('coupon_snapshot')->nullable();
            $table->uuid('shipping_quote_id')->nullable();
            $table->json('shipping_snapshot')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->string('guest_access_token_hash', 64)->nullable()->unique();
            $table->text('guest_access_token_encrypted')->nullable();
            $table->timestamp('placed_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('inventory_restored_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->json('authors')->nullable();
            $table->string('cover_url')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->json('product_snapshot');
            $table->timestamps();
        });

        Schema::create('order_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('shipping');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 10);
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code', 6);
            $table->string('country', 2)->default('IN');
            $table->timestamps();
            $table->unique(['order_id', 'type']);
        });

        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status_type', 20);
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', fn (Blueprint $table) => $table->dropConstrainedForeignId('order_id'));
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('password_setup_required'));
    }
};
