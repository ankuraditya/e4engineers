<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('type');
            $t->boolean('is_enabled')->default(false)->index();
            $t->boolean('is_default')->default(false)->index();
            $t->string('environment')->default('test');
            $t->string('connection_status')->default('not_configured');
            $t->timestamp('last_connection_test_at')->nullable();
            $t->string('last_connection_error', 500)->nullable();
            $t->text('configuration')->nullable();
            $t->unsignedInteger('sort_order')->default(100);
            $t->timestamps();
        });
        Schema::create('payment_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('online_payments_enabled')->default(false);
            $t->boolean('cod_enabled')->default(true);
            $t->decimal('cod_charge', 12, 2)->default(0);
            $t->unsignedInteger('attempt_expiry_minutes')->default(20);
            $t->timestamps();
        });
        Schema::create('payment_attempts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payment_provider_id')->constrained()->restrictOnDelete();
            $t->string('environment');
            $t->string('status')->index();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3);
            $t->string('provider_order_id')->nullable()->index();
            $t->string('provider_payment_id')->nullable()->index();
            $t->string('idempotency_key', 100)->unique();
            $t->json('client_action')->nullable();
            $t->json('failure')->nullable();
            $t->timestamp('expires_at')->index();
            $t->timestamps();
        });
        Schema::create('payment_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('payment_attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('provider_code');
            $t->string('type');
            $t->string('status');
            $t->string('provider_transaction_id')->nullable()->index();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3);
            $t->json('payload')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_refunds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_transaction_id')->constrained()->restrictOnDelete();
            $t->string('status')->index();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3);
            $t->string('provider_refund_id')->nullable()->index();
            $t->string('idempotency_key')->unique();
            $t->string('reason')->nullable();
            $t->json('payload')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider_code');
            $t->string('event_id');
            $t->string('event_type')->nullable();
            $t->string('payload_hash', 64);
            $t->string('status');
            $t->json('payload')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
            $t->unique(['provider_code', 'event_id']);
        });
        Schema::create('payment_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event');
            $t->json('context')->nullable();
            $t->timestamps();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->timestamp('inventory_reserved_at')->nullable();
            $t->timestamp('inventory_finalized_at')->nullable();
            $t->timestamp('inventory_released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['inventory_reserved_at', 'inventory_finalized_at', 'inventory_released_at']));
        Schema::dropIfExists('payment_audit_logs');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payment_settings');
        Schema::dropIfExists('payment_providers');
    }
};
