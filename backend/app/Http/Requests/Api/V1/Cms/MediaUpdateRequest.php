<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;

class MediaUpdateRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['alt_text' => ['nullable', 'string', 'max:255'], 'title' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000']];
    }
}
