<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ContributorResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $primary = $this->disciplines->first(fn ($d) => (bool) $d->pivot->is_primary) ?? $this->disciplines->first();

        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'designation' => $this->designation, 'qualification' => $this->qualification, 'short_bio' => $this->short_bio, 'biography' => $this->when($request->route('slug') !== null, $this->biography), 'expertise_summary' => $this->expertise_summary, 'primary_discipline' => $primary ? ['name' => $primary->name, 'slug' => $primary->slug] : null, 'disciplines' => $this->disciplines->map(fn ($d) => ['name' => $d->name, 'slug' => $d->slug, 'is_primary' => (bool) $d->pivot->is_primary]), 'photo' => $this->media ? ['url' => $this->media->url, 'alt_text' => $this->media->alt_text] : null, 'linkedin_url' => $this->linkedin_url, 'website_url' => $this->website_url, 'is_featured' => $this->is_featured, 'published_at' => $this->published_at, 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo))];
    }
}
