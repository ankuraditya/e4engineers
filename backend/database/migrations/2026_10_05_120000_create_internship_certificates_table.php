<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_certificates', function (Blueprint $table): void {
            $table->id();
            $table->string('candidate_name', 150);
            $table->string('program_title', 200);
            $table->string('mobile_last_four', 4);
            $table->char('lookup_hash', 64)->unique();
            $table->string('file_path');
            $table->string('file_name');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_certificates');
    }
};
