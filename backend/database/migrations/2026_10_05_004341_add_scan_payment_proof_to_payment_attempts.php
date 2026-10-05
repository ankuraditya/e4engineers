<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->string('proof_path')->nullable();
            $table->string('proof_mime', 50)->nullable();
            $table->string('proof_reference', 100)->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->timestamp('proof_reviewed_at')->nullable();
            $table->foreignId('proof_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('proof_review_note', 500)->nullable();
        });
        DB::table('payment_providers')->updateOrInsert(['code' => 'SCANPAY'], [
            'name' => 'Scan & Pay', 'type' => 'manual', 'environment' => 'offline',
            'is_enabled' => false, 'is_default' => false, 'connection_status' => 'not_configured',
            'sort_order' => 20, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proof_reviewed_by');
            $table->dropColumn(['proof_path', 'proof_mime', 'proof_reference', 'proof_uploaded_at', 'proof_reviewed_at', 'proof_review_note']);
        });
        DB::table('payment_providers')->where('code', 'SCANPAY')->whereNotIn('id', DB::table('payment_attempts')->select('payment_provider_id'))->delete();
    }
};
