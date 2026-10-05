<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['settings' => ['required', 'array', 'min:1'], 'settings.*.key' => ['required', 'string', Rule::in(array_keys(config('cms.setting_keys')))], 'settings.*.value' => ['nullable', 'string', 'max:5000']];
    }
}
