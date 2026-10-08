<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

final class RegisterRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $referralCode = mb_strtoupper(trim((string) $this->input('referral_code')));
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'mobile' => $this->normalizeMobile((string) $this->input('mobile')),
            'referral_code' => $referralCode === '' ? null : $referralCode,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'referral_code' => ['nullable', 'string', 'max:16', Rule::exists('users', 'referral_code')],
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
