<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description');
            $table->foreignId('resource_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('engineering_discipline_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('thumbnail_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('preview_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('file_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('access_type', 30)->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->char('currency', 3)->default('INR');
            $table->string('file_format', 12)->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('version')->nullable();
            $table->string('preview_type', 30)->default('none');
            $table->longText('preview_content')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('featured_order')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resource_type_id', 'status']);
            $table->index(['engineering_discipline_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['topic_id', 'status']);
        });

        Schema::create('digital_resource_tag', function (Blueprint $table): void {
            $table->foreignId('digital_resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['digital_resource_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_resource_tag');
        Schema::dropIfExists('digital_resources');
    }
};
