<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiRequest;

final class LoginRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $login = trim((string) $this->input('login'));
        $this->merge([
            'login' => str_contains($login, '@')
                ? mb_strtolower($login)
                : (preg_replace('/\D+/', '', $login) ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
