<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('general');
            $table->string('status')->default('new')->index();
            $table->string('priority')->default('normal');
            $table->string('name');
            $table->string('email')->index();
            $table->string('mobile', 30)->nullable();
            $table->string('subject');
            $table->text('message');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('submission_token')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
        Schema::create('enquiry_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('mobile', 30)->nullable();
            $table->string('category')->default('general');
            $table->string('subject');
            $table->text('description');
            $table->string('status')->default('open')->index();
            $table->string('priority')->default('normal');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('submission_token')->unique();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('support_ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_type');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
        Schema::create('support_ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_ticket_message_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('workshops', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('workshop');
            $table->text('short_description')->nullable();
            $table->longText('description');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('mode')->default('online');
            $table->string('venue')->nullable();
            $table->text('meeting_url')->nullable();
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('registration_type')->default('free');
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('workshop_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('mobile', 30)->nullable();
            $table->string('status')->default('registered');
            $table->text('notes')->nullable();
            $table->uuid('submission_token')->unique();
            $table->timestamp('registered_at');
            $table->timestamps();
            $table->unique(['workshop_id', 'email']);
        });

        Schema::create('job_openings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department');
            $table->string('location');
            $table->string('employment_type');
            $table->string('work_mode')->default('onsite');
            $table->text('summary');
            $table->longText('description');
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->longText('preferred_skills')->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('career_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->nullable()->unique();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('mobile', 30);
            $table->string('current_location')->nullable();
            $table->unsignedTinyInteger('experience')->default(0);
            $table->string('portfolio_url')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('resume_path');
            $table->string('resume_original_name');
            $table->string('resume_mime_type', 100);
            $table->unsignedBigInteger('resume_size');
            $table->string('status')->default('received')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('submission_token')->unique();
            $table->timestamp('applied_at');
            $table->timestamps();
            $table->unique(['job_opening_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_applications');
        Schema::dropIfExists('job_openings');
        Schema::dropIfExists('workshop_registrations');
        Schema::dropIfExists('workshops');
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('enquiry_notes');
        Schema::dropIfExists('enquiries');
    }
};
