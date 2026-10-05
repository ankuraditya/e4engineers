<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Book;
use App\Models\Contributor;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\DigitalResource;
use App\Models\Publication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemoveDemoContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Article::query()->where('content', 'like', '%This development article demonstrates the production content workflow.%')->delete();
            Course::query()->where('description', 'like', '%This development course demonstrates the production course-management workflow.%')
                ->where('slug', '!=', 'fundamentals-of-electrical-and-electronics-engineering')->delete();
            Publication::query()->where('description', 'like', '%This development publication validates the journals and publications workflow.%')->delete();
            DigitalResource::query()->where('description', 'like', '%This development resource validates the study-resource workflow.%')->delete();

            $demoBooks = Book::query()->where('sku', 'like', 'E4E-BOOK-%')
                ->where('description', 'A comprehensive engineering text combining clear theory, worked examples and practical system perspectives.')->get();
            foreach ($demoBooks as $book) {
                $book->cartItems()->delete();
                $book->delete();
            }

            $demoContributors = Contributor::query()->where('short_bio', 'Development-only contributor profile for validating the E4ENGINEERS directory.')->get();
            foreach ($demoContributors as $contributor) {
                $contributor->articles()->detach();
                $contributor->courses()->detach();
                $contributor->disciplines()->detach();
                DB::table('publication_contributor')->where('contributor_id', $contributor->id)->delete();
                $contributor->delete();
            }

            Coupon::query()->where('code', 'E4SAVE10')->where('name', 'E4ENGINEERS 10% Launch Offer')->delete();
        });
    }
}
