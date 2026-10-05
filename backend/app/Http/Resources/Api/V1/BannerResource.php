<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class BannerResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'placement' => $this->placement, 'desktop_media' => $this->whenLoaded('desktopMedia', fn () => new MediaResource($this->desktopMedia)), 'mobile_media' => $this->whenLoaded('mobileMedia', fn () => new MediaResource($this->mobileMedia)), 'desktop_media_id' => $this->when($request->is('api/v1/admin/*'), $this->desktop_media_id), 'mobile_media_id' => $this->when($request->is('api/v1/admin/*'), $this->mobile_media_id), 'eyebrow' => $this->eyebrow, 'heading' => $this->heading, 'description' => $this->description, 'primary_cta' => ['label' => $this->primary_cta_label, 'url' => $this->primary_cta_url], 'secondary_cta' => ['label' => $this->secondary_cta_label, 'url' => $this->secondary_cta_url], 'primary_cta_label' => $this->when($request->is('api/v1/admin/*'), $this->primary_cta_label), 'primary_cta_url' => $this->when($request->is('api/v1/admin/*'), $this->primary_cta_url), 'secondary_cta_label' => $this->when($request->is('api/v1/admin/*'), $this->secondary_cta_label), 'secondary_cta_url' => $this->when($request->is('api/v1/admin/*'), $this->secondary_cta_url), 'sort_order' => $this->sort_order, 'is_active' => $this->when($request->is('api/v1/admin/*'), $this->is_active), 'starts_at' => $this->starts_at, 'ends_at' => $this->ends_at];
    }
}
