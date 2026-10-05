<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\EngineeringDiscipline;
use App\Models\Media;
use App\Models\Tag;
use App\Services\ArticleCache;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegacyWordPressArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $source = file_get_contents(__DIR__.'/legacy-wordpress-posts.json');
        $posts = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $source), true, 512, JSON_THROW_ON_ERROR);
        $discipline = EngineeringDiscipline::where('slug', 'electrical-engineering')->firstOrFail();
        $sanitizer = app(HtmlSanitizer::class);

        foreach ($posts as $index => $post) {
            $categories = $post['_embedded']['wp:term'][0] ?? [];
            $specificCategory = collect($categories)->first(fn (array $category): bool => $category['slug'] !== 'electrical-engineering' && $category['slug'] !== 'course');
            $categoryName = $specificCategory['name'] ?? 'Electrical Engineering';
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName), 'context' => 'article'],
                ['name' => $categoryName, 'is_active' => true, 'sort_order' => 0],
            );

            $featuredSource = $post['_embedded']['wp:featuredmedia'][0] ?? null;
            $featuredMedia = $featuredSource ? $this->mediaFor($featuredSource) : null;
            $html = preg_replace('~<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>~is', '', $post['content']['rendered']);
            $html = preg_replace_callback('~</?h1\b~i', fn (array $match): string => str_replace('h1', 'h2', $match[0]), $html);
            $html = $sanitizer->clean($html);
            $html = preg_replace_callback('/(<img\b[^>]*\bsrc=")([^"]+)(")/i', function (array $match): string {
                $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $path = $this->localPath($url);

                return $match[1].($path && Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : $url).$match[3];
            }, $html);
            $title = trim(html_entity_decode(strip_tags($post['title']['rendered']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $excerpt = trim(html_entity_decode(strip_tags($post['excerpt']['rendered']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $article = Article::firstOrCreate(['slug' => $post['slug']], [
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $html,
                'content_format' => 'html',
                'engineering_discipline_id' => $discipline->id,
                'category_id' => $category->id,
                'featured_media_id' => $featuredMedia?->id,
                'author_name' => 'E4ENGINEERS',
                'status' => 'published',
                'is_featured' => $index < 2,
                'featured_order' => $index + 1,
                'published_at' => $post['date'],
                'reading_time_minutes' => max(1, (int) ceil(str_word_count(strip_tags($html)) / 220)),
                'sort_order' => $index + 1,
            ]);
            if (preg_match('/<h[23](?![^>]*\bid=)/i', $article->content)) {
                $article->content = $this->withHeadingIds($article->content);
                $article->save();
            }

            foreach ($post['_embedded']['wp:term'][1] ?? [] as $term) {
                $tag = Tag::firstOrCreate(['slug' => $term['slug']], ['name' => $term['name'], 'is_active' => true]);
                $article->tags()->syncWithoutDetaching([$tag->id]);
            }
        }

        app(ArticleCache::class)->flush();
    }

    private function mediaFor(array $source): ?Media
    {
        $url = $source['source_url'] ?? '';
        $path = $this->localPath($url);
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $filename = basename($path);
        $file = Storage::disk('public')->path($path);

        return Media::firstOrCreate(['path' => $path], [
            'disk' => 'public',
            'filename' => $filename,
            'original_name' => basename(parse_url($url, PHP_URL_PATH)),
            'mime_type' => mime_content_type($file) ?: 'application/octet-stream',
            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
            'size' => filesize($file),
            'alt_text' => $source['alt_text'] ?: ($source['title']['rendered'] ?? ''),
            'title' => html_entity_decode(strip_tags($source['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'caption' => trim(strip_tags($source['caption']['rendered'] ?? '')),
        ]);
    }

    private function localPath(string $url): ?string
    {
        if (! str_starts_with($url, 'https://e4engineers.in/wp-content/uploads/')) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $path = 'articles/legacy/'.sha1($url).'.'.$extension;
        $asset = __DIR__.'/assets/legacy-articles/'.sha1($url).'.'.$extension;
        if (! is_file($asset)) {
            return null;
        }
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, file_get_contents($asset));
        }

        return $path;
    }

    private function withHeadingIds(string $html): string
    {
        $used = [];

        return preg_replace_callback('/<(h[23])([^>]*)>(.*?)<\/\1>/is', function (array $match) use (&$used): string {
            if (preg_match('/\bid="([^"]+)"/', $match[2], $existing)) {
                $used[$existing[1]] = true;

                return $match[0];
            }
            $base = Str::slug(strip_tags($match[3])) ?: 'section';
            $id = $base;
            $number = 2;
            while (isset($used[$id])) {
                $id = $base.'-'.$number++;
            }
            $used[$id] = true;

            return '<'.$match[1].$match[2].' id="'.$id.'">'.$match[3].'</'.$match[1].'>';
        }, $html) ?? $html;
    }
}
