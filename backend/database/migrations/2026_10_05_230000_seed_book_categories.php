<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Textbooks', 'Reference Books', 'Exam Preparation', 'Handbooks'] as $order => $name) {
            $slug = 'book-'.str($name)->slug();

            if (! DB::table('categories')->where('slug', $slug)->exists()) {
                DB::table('categories')->insert([
                    'name' => $name,
                    'slug' => $slug,
                    'context' => 'book',
                    'sort_order' => $order,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Categories may already be assigned to books; retain them on rollback.
    }
};
