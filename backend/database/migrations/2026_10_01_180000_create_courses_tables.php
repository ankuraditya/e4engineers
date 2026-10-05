<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('short_description')->nullable();
            $t->longText('description');
            $t->foreignId('engineering_discipline_id')->constrained()->restrictOnDelete();
            $t->foreignId('course_level_id')->nullable()->constrained()->nullOnDelete();
            $t->string('mode');
            $t->unsignedInteger('duration_value')->nullable();
            $t->string('duration_unit')->nullable();
            $t->text('eligibility')->nullable();
            $t->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->decimal('fee', 12, 2)->nullable();
            $t->char('currency', 3)->default('INR');
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->string('enrollment_type')->default('enquiry');
            $t->string('enrollment_url', 500)->nullable();
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
        Schema::create('course_learning_outcomes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->text('outcome');
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('course_modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('course_lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('lesson_type')->nullable();
            $t->unsignedInteger('duration_minutes')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('course_contributor', function (Blueprint $t) {
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contributor_id')->constrained()->restrictOnDelete();
            $t->string('role')->default('instructor');
            $t->boolean('is_primary')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->primary(['course_id', 'contributor_id']);
        });
        Schema::create('course_faqs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('question');
            $t->text('answer');
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_faqs');
        Schema::dropIfExists('course_contributor');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('course_learning_outcomes');
        Schema::dropIfExists('courses');
    }
};
