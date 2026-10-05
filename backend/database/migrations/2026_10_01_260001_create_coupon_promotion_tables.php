<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('minimum_subtotal', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('applies_to', 40)->default('all_books')->index();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('coupon_books', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'book_id']);
        });
        Schema::create('coupon_book_categories', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'category_id']);
        });
        Schema::create('coupon_engineering_disciplines', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('engineering_discipline_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'engineering_discipline_id'], 'coupon_discipline_primary');
        });
        Schema::create('coupon_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamp('used_at');
            $table->timestamps();
            $table->index(['coupon_id', 'user_id']);
            $table->unique(['reference_type', 'reference_id']);
        });
        Schema::table('carts', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->nullable()->after('currency')->constrained()->nullOnDelete();
            $table->timestamp('coupon_applied_at')->nullable()->after('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('coupon_applied_at');
        });
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupon_engineering_disciplines');
        Schema::dropIfExists('coupon_book_categories');
        Schema::dropIfExists('coupon_books');
        Schema::dropIfExists('coupons');
    }
};
