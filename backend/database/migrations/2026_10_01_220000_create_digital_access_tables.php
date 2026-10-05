<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publications', fn (Blueprint $t) => $t->foreignId('file_media_id')->nullable()->after('preview_media_id')->constrained('media')->nullOnDelete());
        Schema::create('digital_entitlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('entitleable_type', 30);
            $t->unsignedBigInteger('entitleable_id');
            $t->string('source_type', 30);
            $t->string('source_reference')->nullable();
            $t->timestamp('granted_at');
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamp('revoked_at')->nullable();
            $t->string('status', 20)->index();
            $t->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status']);
            $t->index(['entitleable_type', 'entitleable_id']);
            $t->unique(['source_type', 'source_reference']);
        });
        Schema::create('digital_download_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('downloadable_type', 30);
            $t->unsignedBigInteger('downloadable_id');
            $t->foreignId('entitlement_id')->nullable()->constrained('digital_entitlements')->nullOnDelete();
            $t->ipAddress('ip_address')->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->timestamp('downloaded_at');
            $t->timestamps();
            $t->index(['user_id', 'downloaded_at']);
            $t->index(['downloadable_type', 'downloadable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_download_logs');
        Schema::dropIfExists('digital_entitlements');
        Schema::table('publications', fn (Blueprint $t) => $t->dropConstrainedForeignId('file_media_id'));
    }
};
