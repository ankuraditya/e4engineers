<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ArticleResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $primary = $this->contributors->first(fn ($c) => (bool) $c->pivot->is_primary) ?? $this->contributors->first();

        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'excerpt' => $this->excerpt,
            'content' => $this->when($request->route('slug') !== null, $this->content), 'content_format' => $this->when($request->route('slug') !== null, $this->content_format),
            'discipline' => $this->discipline ? ['name' => $this->discipline->name, 'slug' => $this->discipline->slug] : null,
            'category' => $this->category ? ['name' => $this->category->name, 'slug' => $this->category->slug] : null, 'topic' => $this->topic ? ['name' => $this->topic->name, 'slug' => $this->topic->slug] : null,
            'tags' => $this->tags->map(fn ($x) => ['name' => $x->name, 'slug' => $x->slug]),
            'contributors' => $this->contributors->map(fn ($x) => ['name' => $x->name, 'slug' => $x->slug, 'role' => $x->pivot->role, 'is_primary' => (bool) $x->pivot->is_primary, 'photo' => $x->media ? ['url' => $x->media->url, 'alt_text' => $x->media->alt_text] : null]),
            'author' => $primary ? $primary->name : $this->author_name, 'featured_image' => $this->featuredMedia ? ['url' => $this->featuredMedia->url, 'alt_text' => $this->featuredMedia->alt_text, 'caption' => $this->featuredMedia->caption] : null,
            'published_at' => $this->published_at?->toIso8601String(), 'reading_time_minutes' => $this->reading_time_minutes, 'is_featured' => $this->is_featured,
            'table_of_contents' => $this->when($request->route('slug') !== null, $this->toc()), 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo))];
    }

    private function toc(): array
    {
        preg_match_all('/<h([23])\s+id="([^"]+)"[^>]*>(.*?)<\/h[23]>/is', $this->content, $m, PREG_SET_ORDER);

        return array_map(fn ($x) => ['level' => (int) $x[1], 'id' => $x[2], 'title' => trim(strip_tags($x[3]))], $m);
    }
}
