<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'placement' => ['required', Rule::in(config('cms.banner_placements'))], 'desktop_media_id' => ['nullable', 'exists:media,id'], 'mobile_media_id' => ['nullable', 'exists:media,id'], 'eyebrow' => ['nullable', 'string', 'max:255'], 'heading' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'], 'primary_cta_label' => ['nullable', 'string', 'max:100'], 'primary_cta_url' => ['nullable', 'string', 'max:500'], 'secondary_cta_label' => ['nullable', 'string', 'max:100'], 'secondary_cta_url' => ['nullable', 'string', 'max:500'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_active' => ['sometimes', 'boolean'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at']];
    }
}
