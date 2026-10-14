<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The model encrypts context; ciphertext is not valid JSON for MariaDB's JSON constraint.
        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->longText('context')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->json('context')->nullable()->change();
        });
    }
};
