<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\EngineeringDiscipline;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class ArticleDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        $category = Category::firstOrCreate(['slug' => 'engineering-analysis'], ['name' => 'Engineering Analysis', 'context' => 'article', 'sort_order' => 1, 'is_active' => true]);
        $rows = [
            ['Power Systems Engineering', 'Power System Economics', 'Understand the economic operation and planning of power systems, including cost analysis, pricing mechanisms and market structures.'],
            ['Civil Engineering', 'Smart Infrastructure & Structural Systems', 'Explore modern infrastructure, resilient design and structural systems for sustainable development.'],
            ['Mechanical Engineering', 'Machine Design Fundamentals', 'Learn the principles of machine design, component selection and performance analysis.'],
            ['Computer Science Engineering', 'Introduction to Computer Systems', 'Connect processor architecture with practical embedded-system design.'],
            ['Engineering Mathematics', 'Applied Engineering Mathematics', 'Use modelling and numerical methods to solve multidisciplinary engineering problems.'],
        ];
        foreach ($rows as $i => [$disciplineName,$title,$excerpt]) {
            $discipline = EngineeringDiscipline::where('name', $disciplineName)->first();
            if (! $discipline) {
                continue;
            }
            $topic = Topic::firstOrCreate(['slug' => str($title)->slug()], ['engineering_discipline_id' => $discipline->id, 'name' => $title, 'sort_order' => $i + 1, 'is_active' => true]);
            $tag = Tag::firstOrCreate(['slug' => str($disciplineName)->slug()], ['name' => $disciplineName, 'is_active' => true]);
            $content = '<h2 id="introduction">Introduction</h2><p>'.$excerpt.'</p><div class="article-callout"><strong>Engineering note</strong><p>This development article demonstrates the production content workflow.</p></div><h2 id="technical-principles">Technical principles</h2><p>Engineering decisions balance performance, reliability, safety and cost.</p><div class="formula-block">\\( P_{out} = \\eta P_{in} \\)</div><h2 id="comparison">Comparison</h2><table><thead><tr><th>Factor</th><th>Engineering meaning</th></tr></thead><tbody><tr><td>Performance</td><td>Measured technical output</td></tr><tr><td>Reliability</td><td>Ability to operate consistently</td></tr></tbody></table><h2 id="conclusion">Conclusion</h2><p>Sound analysis connects theory with practical constraints.</p>';
            $article = Article::updateOrCreate(['slug' => str($title)->slug()], ['title' => $title, 'excerpt' => $excerpt, 'content' => $content, 'content_format' => 'html', 'engineering_discipline_id' => $discipline->id, 'category_id' => $category->id, 'topic_id' => $topic->id, 'status' => 'published', 'is_featured' => $i < 3, 'featured_order' => $i + 1, 'published_at' => now()->subDays($i), 'reading_time_minutes' => 2, 'sort_order' => $i + 1]);
            $article->tags()->syncWithoutDetaching([$tag->id]);
            $contributor = Contributor::whereHas('disciplines', fn ($q) => $q->whereKey($discipline->id))->first();
            if ($contributor) {
                $article->contributors()->syncWithoutDetaching([$contributor->id => ['role' => 'author', 'is_primary' => true, 'sort_order' => 0]]);
            }
        }
    }
}
