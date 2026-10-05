<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->unique()->constrained('books')->restrictOnDelete();
            $table->unsignedInteger('stock_quantity')->default(0)->index();
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('is_backorder_allowed')->default(false);
            $table->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->string('type')->index();
            $table->integer('quantity');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['reference_type', 'reference_id']);
        });
        $threshold = (int) config('e4engineers.inventory.low_stock_threshold', 5);
        DB::table('books')->select('id')->orderBy('id')->each(fn ($book) => DB::table('book_inventory')->insert(['book_id' => $book->id, 'stock_quantity' => 0, 'reserved_quantity' => 0, 'low_stock_threshold' => $threshold, 'created_at' => now(), 'updated_at' => now()]));
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('book_inventory');
    }
};
