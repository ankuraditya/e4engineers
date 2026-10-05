<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class PageSectionRequest extends ApiRequest
{
    public function rules(): array
    {
        $page = $this->route('page')?->id;
        $id = $this->route('section')?->id;

        return [
            'section_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('page_sections')->where('page_id', $page)->ignore($id)],
            'heading' => ['nullable', 'string', 'max:255'], 'subheading' => ['nullable', 'string', 'max:500'], 'content' => ['nullable', 'string', 'max:100000'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'], 'settings' => ['nullable', 'array'], 'settings.alignment' => ['sometimes', Rule::in(['left', 'center', 'right'])],
            'settings.cta_label' => ['sometimes', 'string', 'max:100'], 'settings.cta_url' => ['sometimes', 'string', 'max:500'], 'settings.layout_variant' => ['sometimes', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
