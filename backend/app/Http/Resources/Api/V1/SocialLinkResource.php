<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class SocialLinkResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'platform' => $this->platform, 'label' => $this->label, 'url' => $this->url, 'icon' => $this->icon, 'sort_order' => $this->sort_order, 'is_active' => $this->when($request->is('api/v1/admin/*'), $this->is_active)];
    }
}
