<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PageSectionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'section_key' => $this->section_key, 'heading' => $this->heading, 'subheading' => $this->subheading, 'content' => $this->content, 'media' => $this->whenLoaded('media', fn () => new MediaResource($this->media)), 'settings' => $this->settings, 'sort_order' => $this->sort_order, 'is_active' => $this->is_active];
    }
}
