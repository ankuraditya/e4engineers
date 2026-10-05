<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $t): void {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('short_description')->nullable();
            $t->longText('content')->nullable();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('engineering_discipline_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->foreignId('attachment_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('external_url')->nullable();
            $t->date('notice_date')->index();
            $t->timestamp('expires_at')->nullable()->index();
            $t->string('status')->default('draft')->index();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_pinned')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable()->index();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('gallery_albums', function (Blueprint $t): void {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('short_description')->nullable();
            $t->longText('description')->nullable();
            $t->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->foreignId('engineering_discipline_id')->nullable()->constrained()->nullOnDelete();
            $t->date('event_date')->nullable();
            $t->string('status')->default('draft')->index();
            $t->boolean('is_featured')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('gallery_images', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('gallery_album_id')->constrained()->cascadeOnDelete();
            $t->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $t->string('title')->nullable();
            $t->text('caption')->nullable();
            $t->string('alt_text')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_featured')->default(false);
            $t->timestamps();
            $t->unique(['gallery_album_id', 'media_id']);
        });
        Schema::create('videos', function (Blueprint $t): void {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('short_description')->nullable();
            $t->longText('description')->nullable();
            $t->string('video_type');
            $t->string('external_video_id')->nullable();
            $t->string('external_url')->nullable();
            $t->foreignId('thumbnail_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->foreignId('engineering_discipline_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->date('published_date')->nullable();
            $t->string('status')->default('draft')->index();
            $t->boolean('is_featured')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable()->index();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('gallery_albums');
        Schema::dropIfExists('notices');
    }
};
