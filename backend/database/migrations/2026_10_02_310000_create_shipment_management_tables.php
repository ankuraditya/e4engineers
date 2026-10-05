<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table) {
            $table->boolean('automatic_shipment_creation')->default(false);
            $table->boolean('automatic_awb_assignment')->default(false);
            $table->boolean('automatic_pickup_scheduling')->default(false);
            $table->boolean('automatic_label_generation')->default(false);
            $table->boolean('automatic_manifest_generation')->default(false);
            $table->foreignId('default_pickup_location_id')->nullable()->constrained('shipping_pickup_locations')->nullOnDelete();
            $table->unsignedInteger('tracking_sync_minutes')->default(60);
        });
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('shipping_provider_id')->constrained()->restrictOnDelete();
            $table->string('provider_order_id')->nullable()->index();
            $table->string('provider_shipment_id')->nullable()->index();
            $table->string('awb_number')->nullable()->unique();
            $table->string('courier_code')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('service_name')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('provider_status')->nullable();
            $table->string('payment_mode');
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->decimal('shipping_charge_snapshot', 12, 2)->default(0);
            $table->unsignedInteger('weight_grams');
            $table->decimal('length_cm', 8, 2);
            $table->decimal('width_cm', 8, 2);
            $table->decimal('height_cm', 8, 2);
            $table->json('pickup_location_snapshot');
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('last_tracked_at')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('label_path')->nullable();
            $table->string('manifest_path')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('shipment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->unsignedInteger('attempt_number');
            $table->string('action');
            $table->string('idempotency_key')->unique();
            $table->string('status')->index();
            $table->string('provider_reference')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('shipment_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('provider_event_id')->nullable();
            $table->string('status')->index();
            $table->string('provider_status')->nullable();
            $table->string('label');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->string('source');
            $table->string('event_hash', 64);
            $table->timestamps();
            $table->unique(['shipment_id', 'event_hash']);
        });
        Schema::create('shipment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id');
            $table->string('payload_hash', 64);
            $table->string('status');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
        Schema::create('shipment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_audit_logs');
        Schema::dropIfExists('shipment_webhook_events');
        Schema::dropIfExists('shipment_tracking_events');
        Schema::dropIfExists('shipment_attempts');
        Schema::dropIfExists('shipments');
        Schema::table('shipping_settings', fn (Blueprint $table) => $table->dropColumn(['automatic_shipment_creation', 'automatic_awb_assignment', 'automatic_pickup_scheduling', 'automatic_label_generation', 'automatic_manifest_generation', 'default_pickup_location_id', 'tracking_sync_minutes']));
    }
};
