<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 16)->nullable()->unique();
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('referral_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('qualifying_order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('amount_paise');
            $table->timestamp('credited_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('store_credit_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referral_reward_id')->nullable()->unique()->constrained('referral_rewards')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->constrained('orders')->nullOnDelete();
            $table->integer('amount_paise');
            $table->string('reason', 40);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('store_credit_total', 12, 2)->default(0);
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('store_credit_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('store_credit_amount'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('store_credit_total'));
        Schema::dropIfExists('store_credit_entries');
        Schema::dropIfExists('referral_rewards');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
