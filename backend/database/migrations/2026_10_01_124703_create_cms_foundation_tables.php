<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 40)->default('public');
            $table->string('path')->unique();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 100)->index();
            $table->string('extension', 12);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('page_type', 30)->default('standard')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('template', 80)->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['page_id', 'section_key']);
            $table->index(['page_id', 'sort_order']);
        });
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('robots', 30)->default('index,follow');
            $table->json('structured_data')->nullable();
            $table->timestamps();
        });
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group', 40)->index();
            $table->string('type', 30)->default('string');
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 40);
            $table->string('label');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('placement', 40)->index();
            $table->foreignId('desktop_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('mobile_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->text('description')->nullable();
            $table->string('primary_cta_label')->nullable();
            $table->string('primary_cta_url')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['placement', 'sort_order']);
        });
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path')->unique();
            $table->string('target_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('website_settings');
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('media');
    }
};
