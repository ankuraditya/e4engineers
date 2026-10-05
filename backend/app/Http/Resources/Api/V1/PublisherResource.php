<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PublisherResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description, 'website_url' => $this->website_url, 'logo' => $this->logo ? ['url' => $this->logo->url, 'alt_text' => $this->logo->alt_text] : null, 'is_active' => $this->is_active, 'sort_order' => $this->sort_order];
    }
}
