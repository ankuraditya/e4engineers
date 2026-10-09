<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table): void {
            $table->boolean('self_collect_enabled')->default(false);
            $table->foreignId('self_collect_location_id')->nullable()->constrained('shipping_pickup_locations')->nullOnDelete();
            $table->string('self_collect_hours', 255)->nullable();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_method', 20)->default('courier')->index();
            $table->timestamp('pickup_ready_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['delivery_method', 'pickup_ready_at', 'picked_up_at']));
        Schema::table('shipping_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('self_collect_location_id');
            $table->dropColumn(['self_collect_enabled', 'self_collect_hours']);
        });
    }
};
