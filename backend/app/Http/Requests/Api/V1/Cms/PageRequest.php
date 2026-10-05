<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class PageRequest extends ApiRequest
{
    public function rules(): array
    {
        $id = $this->route('page')?->id ?? $this->route('page');

        return [
            'title' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($id)],
            'page_type' => ['required', Rule::in(config('cms.page_types'))], 'status' => ['sometimes', Rule::in(config('cms.page_statuses'))],
            'excerpt' => ['nullable', 'string', 'max:1000'], 'content' => ['nullable', 'string', 'max:200000'], 'template' => ['nullable', 'string', 'max:80'],
        ];
    }
}
