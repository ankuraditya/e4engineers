<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;

class SocialLinkRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['platform' => ['required', 'string', 'max:40'], 'label' => ['required', 'string', 'max:100'], 'url' => ['required', 'url:http,https', 'max:500'], 'icon' => ['nullable', 'string', 'max:100'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_active' => ['sometimes', 'boolean']];
    }
}
