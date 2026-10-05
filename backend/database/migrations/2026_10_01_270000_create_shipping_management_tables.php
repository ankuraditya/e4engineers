<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('is_enabled')->default(false)->index();
            $t->boolean('is_default')->default(false)->index();
            $t->unsignedInteger('priority')->default(100);
            $t->string('connection_status')->default('not_configured');
            $t->timestamp('last_connection_test_at')->nullable();
            $t->string('last_connection_error', 500)->nullable();
            $t->text('configuration')->nullable();
            $t->timestamps();
        });
        Schema::create('shipping_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('shipping_enabled')->default(false);
            $t->string('mode')->default('hybrid');
            $t->foreignId('default_provider_id')->nullable()->constrained('shipping_providers')->nullOnDelete();
            $t->foreignId('fallback_provider_id')->nullable()->constrained('shipping_providers')->nullOnDelete();
            $t->boolean('automatic_fallback')->default(true);
            $t->boolean('fallback_to_flat_rate')->default(false);
            $t->boolean('free_shipping_enabled')->default(false);
            $t->decimal('free_shipping_threshold', 12, 2)->nullable();
            $t->boolean('flat_shipping_enabled')->default(true);
            $t->decimal('flat_shipping_charge', 12, 2)->default(80);
            $t->boolean('provider_live_rates_enabled')->default(true);
            $t->boolean('show_delivery_estimate')->default(true);
            $t->unsignedInteger('default_package_weight_grams')->default(500);
            $t->decimal('default_length_cm', 8, 2)->default(20);
            $t->decimal('default_width_cm', 8, 2)->default(15);
            $t->decimal('default_height_cm', 8, 2)->default(5);
            $t->unsignedInteger('handling_days')->default(1);
            $t->decimal('rate_markup_percentage', 5, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('shipping_pickup_locations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('company_name')->nullable();
            $t->string('contact_name');
            $t->string('phone', 20);
            $t->string('email')->nullable();
            $t->string('address_line1');
            $t->string('address_line2')->nullable();
            $t->string('city');
            $t->string('state');
            $t->string('postal_code', 10)->index();
            $t->string('country', 2)->default('IN');
            $t->boolean('is_default')->default(false)->index();
            $t->boolean('is_active')->default(true);
            $t->text('provider_metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('shipping_quotes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->string('provider_code')->nullable();
            $t->string('courier_code')->nullable();
            $t->string('courier_name');
            $t->decimal('charge', 12, 2);
            $t->decimal('cod_charge', 12, 2)->default(0);
            $t->string('estimated_delivery')->nullable();
            $t->boolean('cod_available')->default(false);
            $t->timestamp('quoted_at');
            $t->timestamp('expires_at')->index();
            $t->string('request_hash', 64)->index();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('shipping_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('shipping_provider_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event');
            $t->json('context')->nullable();
            $t->timestamps();
        });
        Schema::table('books', function (Blueprint $t) {
            $t->unsignedInteger('weight_grams')->nullable();
            $t->decimal('length_cm', 8, 2)->nullable();
            $t->decimal('width_cm', 8, 2)->nullable();
            $t->decimal('height_cm', 8, 2)->nullable();
            $t->boolean('shipping_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $t) {
            $t->dropColumn(['weight_grams', 'length_cm', 'width_cm', 'height_cm', 'shipping_enabled']);
        });
        Schema::dropIfExists('shipping_audit_logs');
        Schema::dropIfExists('shipping_quotes');
        Schema::dropIfExists('shipping_pickup_locations');
        Schema::dropIfExists('shipping_settings');
        Schema::dropIfExists('shipping_providers');
    }
};
