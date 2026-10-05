<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('biography')->nullable();
            $t->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('website_url')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('publishers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('website_url')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('books', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('sku')->unique();
            $t->string('isbn')->nullable()->unique();
            $t->text('short_description')->nullable();
            $t->longText('description');
            $t->foreignId('engineering_discipline_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('publisher_id')->nullable()->constrained()->nullOnDelete();
            $t->string('edition')->nullable();
            $t->unsignedSmallInteger('publication_year')->nullable();
            $t->string('language')->default('English');
            $t->unsignedInteger('pages')->nullable();
            $t->string('format')->default('paperback');
            $t->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->decimal('mrp', 12, 2);
            $t->decimal('selling_price', 12, 2);
            $t->char('currency', 3)->default('INR');
            $t->string('status')->default('draft')->index();
            $t->boolean('is_featured')->default(false)->index();
            $t->unsignedInteger('featured_order')->nullable();
            $t->boolean('is_new_arrival')->default(false)->index();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable()->index();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['engineering_discipline_id', 'category_id']);
            $t->index(['publisher_id', 'selling_price']);
        });
        Schema::create('author_book', function (Blueprint $t) {
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->foreignId('author_id')->constrained()->restrictOnDelete();
            $t->string('role')->default('author');
            $t->unsignedInteger('sort_order')->default(0);
            $t->primary(['book_id', 'author_id', 'role']);
        });
        Schema::create('book_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
            $t->unique(['book_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_images');
        Schema::dropIfExists('author_book');
        Schema::dropIfExists('books');
        Schema::dropIfExists('publishers');
        Schema::dropIfExists('authors');
    }
};
