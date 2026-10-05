<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

final class UpdateAdminUserRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim((string) $this->input('name'));
        }

        if ($this->has('email')) {
            $data['email'] = mb_strtolower(trim((string) $this->input('email')));
        }

        if ($this->has('mobile')) {
            $data['mobile'] = preg_replace('/\D+/', '', (string) $this->input('mobile')) ?: null;
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->getKey();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['sometimes', 'nullable', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'mobile')->ignore($userId)],
            'roles' => ['sometimes', 'required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(config('admin_authorization.admin_roles'))],
        ];
    }
}
