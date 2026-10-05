<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_engineering_disciplines', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('engineering_discipline_id')->constrained()->cascadeOnDelete();
            $table->primary(['book_id', 'engineering_discipline_id'], 'book_discipline_primary');
            $table->index(['engineering_discipline_id', 'book_id'], 'book_discipline_lookup');
        });

        DB::table('books')->whereNotNull('engineering_discipline_id')->orderBy('id')->chunk(500, function ($books) {
            DB::table('book_engineering_disciplines')->insert($books->map(fn ($book) => [
                'book_id' => $book->id,
                'engineering_discipline_id' => $book->engineering_discipline_id,
            ])->all());
        });

        if (! DB::table('engineering_disciplines')->where('slug', 'metallurgy-engineering')->exists()) {
            DB::table('engineering_disciplines')->insert([
                'name' => 'Metallurgy Engineering',
                'slug' => 'metallurgy-engineering',
                'short_name' => 'Metallurgy',
                'description' => 'Metals, materials processing, alloys and industrial applications.',
                'sort_order' => 9,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('book_engineering_disciplines');
    }
};
