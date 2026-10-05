<?php

namespace App\Http\Requests\Api\V1\Account;

use App\Enums\AddressType;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StoreCustomerAddressRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input('mobile')) ?? '';
        $this->merge(['mobile' => str_starts_with($digits, '91') && strlen($digits) === 12 ? substr($digits, 2) : $digits, 'country_code' => strtoupper((string) $this->input('country_code', 'IN'))]);
    }

    public function rules(): array
    {
        return ['type' => ['required', Rule::enum(AddressType::class)], 'full_name' => 'required|string|max:120', 'mobile' => 'required|regex:/^[6-9][0-9]{9}$/', 'address_line_1' => 'required|string|max:255', 'address_line_2' => 'nullable|string|max:255', 'landmark' => 'nullable|string|max:150', 'city' => 'required|string|max:100', 'state' => 'required|string|max:100', 'postal_code' => 'required|regex:/^[1-9][0-9]{5}$/', 'country_code' => 'required|in:IN', 'is_default' => 'sometimes|boolean'];
    }
}
