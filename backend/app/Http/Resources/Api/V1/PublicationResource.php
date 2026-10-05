<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PublicationResource extends ApiResource
{
    public function toArray(Request $r): array
    {
        $detail = $r->route('slug') !== null;
        $authors = $this->contributors->where('pivot.role', 'author');
        $editors = $this->contributors->where('pivot.role', 'editor');

        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'short_description' => $this->short_description, 'description' => $this->when($detail, $this->description), 'type' => $this->type ? ['name' => $this->type->name, 'slug' => $this->type->slug] : null, 'discipline' => $this->discipline ? ['name' => $this->discipline->name, 'slug' => $this->discipline->slug] : null, 'category' => $this->category ? ['name' => $this->category->name, 'slug' => $this->category->slug] : null, 'cover' => $this->featuredMedia ? ['url' => $this->featuredMedia->url, 'alt_text' => $this->featuredMedia->alt_text, 'caption' => $this->featuredMedia->caption] : null, 'authors' => $authors->map(fn ($x) => ['name' => $x->name, 'slug' => $x->slug])->values(), 'editors' => $editors->map(fn ($x) => ['name' => $x->name, 'slug' => $x->slug])->values(), 'author_summary' => $authors->pluck('name')->join(', ') ?: $this->author_text, 'editor_summary' => $editors->pluck('name')->join(', ') ?: $this->editor_text, 'publication_date' => $this->publication_date, 'volume' => $this->volume, 'issue' => $this->issue, 'pages' => $this->pages, 'access_type' => $this->access_type->value, 'price' => $this->price, 'currency' => $this->currency, 'preview' => $this->when($detail, ['type' => $this->preview_type->value, 'content' => $this->preview_content, 'media' => $this->previewMedia ? ['url' => $this->previewMedia->url, 'alt_text' => $this->previewMedia->alt_text] : null]), 'download_ready' => false, 'is_featured' => $this->is_featured, 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo))];
    }
}
