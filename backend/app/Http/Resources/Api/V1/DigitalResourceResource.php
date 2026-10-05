<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class DigitalResourceResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $detail = $request->route('slug') !== null;
        $access = $this->access_type->value;

        return [
            'id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'short_description' => $this->short_description,
            'description' => $this->when($detail, $this->description),
            'type' => $this->type ? ['name' => $this->type->name, 'slug' => $this->type->slug] : null,
            'discipline' => $this->discipline ? ['name' => $this->discipline->name, 'slug' => $this->discipline->slug] : null,
            'category' => $this->category ? ['name' => $this->category->name, 'slug' => $this->category->slug] : null,
            'topic' => $this->topic ? ['name' => $this->topic->name, 'slug' => $this->topic->slug] : null,
            'tags' => $this->tags->map(fn ($tag) => ['name' => $tag->name, 'slug' => $tag->slug]),
            'thumbnail' => $this->thumbnail ? ['url' => $this->thumbnail->url, 'alt_text' => $this->thumbnail->alt_text, 'caption' => $this->thumbnail->caption] : null,
            'preview' => $this->when($detail, ['type' => $this->preview_type->value, 'content' => $this->preview_content, 'media' => $this->previewMedia ? ['url' => $this->previewMedia->url, 'alt_text' => $this->previewMedia->alt_text] : null]),
            'access_type' => $access, 'price' => $this->price, 'currency' => $this->currency, 'file_format' => $this->file_format, 'pages' => $this->pages,
            'file_size' => $this->file_size, 'file_size_display' => $this->file_size ? number_format($this->file_size / 1048576, 1).' MB' : null, 'version' => $this->version,
            'access' => ['type' => $access, 'requires_login' => $access !== 'free', 'requires_purchase' => $access === 'paid', 'has_file' => (bool) $this->file_media_id, 'download_available' => false],
            'is_featured' => $this->is_featured, 'published_at' => $this->published_at?->toISOString(), 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo)),
        ];
    }
}
