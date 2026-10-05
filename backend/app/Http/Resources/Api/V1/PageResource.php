<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PageResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->when($request->is('api/v1/admin/*'), $this->id), 'title' => $this->title, 'slug' => $this->slug, 'page_type' => $this->page_type->value, 'status' => $this->when($request->is('api/v1/admin/*'), $this->status->value), 'excerpt' => $this->excerpt, 'content' => $this->content, 'template' => $this->template, 'is_system' => $this->when($request->is('api/v1/admin/*'), $this->is_system), 'published_at' => $this->published_at, 'sections' => PageSectionResource::collection($this->whenLoaded('sections')), 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo))];
    }
}
