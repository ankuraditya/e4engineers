<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->text('provider_label_url')->nullable();
        });

        DB::table('shipping_providers')->insertOrIgnore([
            'code' => 'SHIPPRIME',
            'name' => 'ShipPrime',
            'priority' => 30,
            'is_enabled' => false,
            'is_default' => false,
            'connection_status' => 'not_configured',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('provider_label_url');
        });
    }
};
