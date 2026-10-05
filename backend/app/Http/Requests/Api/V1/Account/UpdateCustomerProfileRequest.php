<?php

namespace App\Http\Requests\Api\V1\Account;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input('mobile')) ?? '';
        $this->merge(['name' => trim((string) $this->input('name')), 'email' => mb_strtolower(trim((string) $this->input('email'))), 'mobile' => str_starts_with($digits, '91') && strlen($digits) === 12 ? substr($digits, 2) : $digits]);
    }

    public function rules(): array
    {
        $id = $this->user()->id;

        return ['name' => 'required|string|max:120', 'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($id)], 'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users')->ignore($id)]];
    }
}
