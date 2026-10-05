<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class RedirectRequest extends ApiRequest
{
    public function rules(): array
    {
        $id = $this->route('redirect')?->id;

        return ['source_path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)[^\s]*$/', Rule::unique('redirects')->ignore($id)], 'target_path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)[^\s]*$/'], 'status_code' => ['required', 'integer', Rule::in(config('cms.redirect_codes'))], 'is_active' => ['sometimes', 'boolean']];
    }
}
