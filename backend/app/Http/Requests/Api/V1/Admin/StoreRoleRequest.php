<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

final class StoreRoleRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => mb_strtolower(trim((string) $this->input('name')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('roles', 'name')]];
    }
}
