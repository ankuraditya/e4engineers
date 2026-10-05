<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;

class MediaUploadRequest extends ApiRequest
{
    public function rules(): array
    {
        $private = $this->input('storage') === 'private';

        return ['file' => ['required', 'file', 'mimes:'.implode(',', $private ? ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip'] : config('cms.media_mimes')), 'max:'.($private ? 51200 : config('cms.media_max_kb'))], 'storage' => ['sometimes', 'in:public,private'], 'alt_text' => ['nullable', 'string', 'max:255'], 'title' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:2000']];
    }
}
