<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class AuthorResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'biography' => $this->biography, 'website_url' => $this->website_url, 'photo' => $this->photo ? ['url' => $this->photo->url, 'alt_text' => $this->photo->alt_text] : null, 'is_active' => $this->is_active, 'sort_order' => $this->sort_order];
    }
}
