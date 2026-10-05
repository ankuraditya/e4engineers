<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'mobile' => $this->normalizeMobile((string) $this->input('mobile')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        return str_starts_with($digits, '91') && strlen($digits) === 12
            ? substr($digits, 2)
            : $digits;
    }
}
