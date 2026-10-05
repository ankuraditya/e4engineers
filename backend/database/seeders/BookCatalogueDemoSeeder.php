<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\EngineeringDiscipline;
use App\Models\Publisher;
use Illuminate\Database\Seeder;

class BookCatalogueDemoSeeder extends Seeder
{
    public function run(): void
    {
        $publisher = Publisher::updateOrCreate(['slug' => 'e4engineers-academic-publications'], ['name' => 'E4ENGINEERS Academic Publications', 'description' => 'Engineering texts for students and professionals.', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'academic-textbook'], ['name' => 'Academic Textbook', 'context' => 'book', 'is_active' => true]);
        $disciplines = EngineeringDiscipline::where('is_active', true)->get();
        $titles = ['Electrical Power Systems', 'Engineering Mathematics', 'Mechanical Engineering', 'Civil Engineering'];
        $authors = ['Dr. S. K. Rao', 'Prof. Anita Sen', 'R. K. Mehta', 'Dr. Kavita Iyer'];
        foreach ($titles as $i => $title) {
            $author = Author::updateOrCreate(['slug' => str($authors[$i])->slug()], ['name' => $authors[$i], 'is_active' => true, 'sort_order' => $i]);
            $book = Book::updateOrCreate(['sku' => 'E4E-BOOK-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)], ['title' => $title, 'slug' => str($title)->slug(), 'isbn' => '978819400'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'short_description' => 'A practical engineering reference for academic and professional learning.', 'description' => 'A comprehensive engineering text combining clear theory, worked examples and practical system perspectives.', 'engineering_discipline_id' => $disciplines->get($i)?->id, 'category_id' => $category->id, 'publisher_id' => $publisher->id, 'edition' => '2026 Edition', 'publication_year' => 2026, 'language' => 'English', 'pages' => 420 + $i * 20, 'format' => 'paperback', 'mrp' => 599 + $i * 50, 'selling_price' => 499 + $i * 50, 'currency' => 'INR', 'status' => 'published', 'is_featured' => true, 'featured_order' => $i, 'is_new_arrival' => $i > 1, 'sort_order' => $i, 'published_at' => now()]);
            $book->authors()->sync([$author->id => ['role' => 'author', 'sort_order' => 0]]);
        }
    }
}
