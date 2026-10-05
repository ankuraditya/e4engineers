<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('short_description')->nullable();
            $t->longText('description');
            $t->foreignId('publication_type_id')->constrained()->restrictOnDelete();
            $t->foreignId('engineering_discipline_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('author_text')->nullable();
            $t->string('editor_text')->nullable();
            $t->date('publication_date')->nullable()->index();
            $t->string('volume')->nullable();
            $t->string('issue')->nullable();
            $t->unsignedInteger('pages')->nullable();
            $t->string('access_type')->default('free')->index();
            $t->decimal('price', 12, 2)->nullable();
            $t->char('currency', 3)->default('INR');
            $t->string('preview_type')->default('none');
            $t->longText('preview_content')->nullable();
            $t->foreignId('preview_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('status')->default('draft')->index();
            $t->boolean('is_featured')->default(false)->index();
            $t->unsignedInteger('featured_order')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable()->index();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('publication_contributor', function (Blueprint $t) {
            $t->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contributor_id')->constrained()->restrictOnDelete();
            $t->string('role')->default('author');
            $t->unsignedInteger('sort_order')->default(0);
            $t->primary(['publication_id', 'contributor_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_contributor');
        Schema::dropIfExists('publications');
    }
};
