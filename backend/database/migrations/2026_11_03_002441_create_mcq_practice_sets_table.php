<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcq_practice_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subject', 120)->index();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->json('questions');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcq_practice_sets');
    }
};
