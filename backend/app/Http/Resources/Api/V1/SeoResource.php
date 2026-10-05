<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class SeoResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['meta_title' => $this->meta_title, 'meta_description' => $this->meta_description, 'canonical_url' => $this->canonical_url, 'og_title' => $this->og_title, 'og_description' => $this->og_description, 'og_image' => $this->ogMedia?->url, 'robots' => $this->robots, 'structured_data' => $this->structured_data];
    }
}
