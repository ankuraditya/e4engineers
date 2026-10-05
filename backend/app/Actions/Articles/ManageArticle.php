<?php

namespace App\Actions\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\ArticleCache;
use App\Services\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManageArticle
{
    public function __construct(private ArticleCache $cache, private HtmlSanitizer $sanitizer) {}

    public function create(array $data, int $userId): Article
    {
        return $this->persist(new Article, $data, $userId, true);
    }

    public function update(Article $article, array $data, int $userId): Article
    {
        return $this->persist($article, $data, $userId, false);
    }

    private function persist(Article $article, array $data, int $userId, bool $creating): Article
    {
        return DB::transaction(function () use ($article, $data, $userId, $creating) {
            $tags = $data['tag_ids'] ?? null;
            $contributors = $data['contributors'] ?? null;
            unset($data['tag_ids'], $data['contributors']);
            if (array_key_exists('content', $data)) {
                $data['content'] = $this->withHeadingIds($this->sanitizer->clean($data['content']) ?? '');
                $data['reading_time_minutes'] = max(1, (int) ceil(str_word_count(strip_tags($data['content'])) / 220));
            }
            if ($creating) {
                $data['slug'] ??= Str::slug($data['title']);
                $data['created_by'] = $userId;
            }
            $data['updated_by'] = $userId;
            if (($data['status'] ?? null) === ArticleStatus::Published->value && empty($data['published_at'])) {
                $data['published_at'] = now();
            }
            $article->fill($data)->save();
            if ($tags !== null) {
                $article->tags()->sync($tags);
            }
            if ($contributors !== null) {
                $pivot = [];
                foreach ($contributors as $order => $item) {
                    $pivot[$item['id']] = ['role' => $item['role'] ?? 'author', 'is_primary' => $item['is_primary'] ?? $order === 0, 'sort_order' => $order];
                } $article->contributors()->sync($pivot);
            }
            $this->cache->flush();

            return $article->refresh()->load(['discipline', 'category', 'topic', 'tags', 'contributors', 'featuredMedia', 'seo.ogMedia']);
        });
    }

    private function withHeadingIds(string $html): string
    {
        $used = [];

        return preg_replace_callback('/<(h[23])([^>]*)>(.*?)<\/h[23]>/is', function ($m) use (&$used) {
            $text = trim(strip_tags($m[3]));
            $base = Str::slug($text) ?: 'section';
            $id = $base;
            $i = 2;
            while (isset($used[$id])) {
                $id = $base.'-'.$i++;
            } $used[$id] = true;

            return '<'.$m[1].' id="'.$id.'">'.$m[3].'</'.$m[1].'>';
        }, $html) ?? $html;
    }
}
