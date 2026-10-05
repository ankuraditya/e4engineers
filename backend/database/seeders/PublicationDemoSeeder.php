<?php

namespace Database\Seeders;

use App\Models\Contributor;
use App\Models\EngineeringDiscipline;
use App\Models\Publication;
use App\Models\PublicationType;
use Illuminate\Database\Seeder;

class PublicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }$rows = [['Journal', 'Electrical Engineering', 'International Journal of Electrical Engineering'], ['Journal', 'Mechanical Engineering', 'Journal of Mechanical Engineering'], ['Journal', 'Civil Engineering', 'Journal of Civil Engineering'], ['Journal', 'Mining Engineering', 'Journal of Mining Engineering'], ['Technical Publication', 'Electronics Engineering', 'Journal of Electronics and Computer Systems'], ['Journal', 'Power Systems Engineering', 'Journal of Power and Energy Systems'], ['Journal', 'Engineering Mathematics', 'Journal of Applied Mathematics']];
        foreach ($rows as $i => [$type,$discipline,$title]) {
            $t = PublicationType::where('name', $type)->first();
            $d = EngineeringDiscipline::where('name', $discipline)->first();
            if (! $t || ! $d) {
                continue;
            }$p = Publication::updateOrCreate(['slug' => str($title)->slug()], ['title' => $title, 'short_description' => 'Development issue presenting engineering research and technical perspectives.', 'description' => '<p>This development publication validates the journals and publications workflow.</p>', 'publication_type_id' => $t->id, 'engineering_discipline_id' => $d->id, 'editor_text' => 'E4ENGINEERS Editorial Desk', 'publication_date' => now()->subMonths($i), 'volume' => 'Volume 1', 'issue' => 'Issue '.($i + 1), 'pages' => 120 + $i * 8, 'access_type' => $i % 3 === 0 ? 'paid' : 'free', 'price' => $i % 3 === 0 ? 499 : null, 'currency' => 'INR', 'preview_type' => 'text', 'preview_content' => '<p>Preview abstracts and selected issue information.</p>', 'status' => 'published', 'is_featured' => true, 'featured_order' => $i + 1, 'published_at' => now()->subDays($i)]);
            $person = Contributor::whereHas('disciplines', fn ($q) => $q->whereKey($d->id))->first();
            if ($person) {
                $p->contributors()->syncWithoutDetaching([$person->id => ['role' => 'editor', 'sort_order' => 0]]);
            }
        }
    }
}
