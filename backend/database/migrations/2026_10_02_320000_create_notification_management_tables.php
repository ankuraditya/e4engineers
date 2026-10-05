<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('mailer')->default('smtp');
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->default('E4ENGINEERS');
            $table->string('reply_to_email')->nullable();
            $table->boolean('queue_enabled')->default(true);
            $table->string('connection_status')->default('not_tested');
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('channel');
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_enabled')->default(true);
            $table->json('available_variables');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['type', 'channel']);
        });
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('deduplication_key')->unique();
            $table->string('type')->index();
            $table->string('channel')->index();
            $table->string('recipient');
            $table->nullableMorphs('notifiable');
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->string('status')->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable()->index();
            $table->json('context')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('order_updates')->default(true);
            $table->boolean('payment_updates')->default(true);
            $table->boolean('shipping_updates')->default(true);
            $table->boolean('learning_updates')->default(true);
            $table->boolean('promotional_communications')->default(false);
            $table->timestamps();
        });
        Schema::create('notification_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_audit_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('email_settings');
    }
};
