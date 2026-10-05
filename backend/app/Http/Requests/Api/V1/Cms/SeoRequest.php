<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class SeoRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['meta_title' => ['nullable', 'string', 'max:255'], 'meta_description' => ['nullable', 'string', 'max:1000'], 'canonical_url' => ['nullable', 'url:http,https', 'max:1000'], 'og_title' => ['nullable', 'string', 'max:255'], 'og_description' => ['nullable', 'string', 'max:1000'], 'og_media_id' => ['nullable', 'exists:media,id'], 'robots' => ['sometimes', Rule::in(config('cms.robots'))], 'structured_data' => ['nullable', 'array']];
    }
}
