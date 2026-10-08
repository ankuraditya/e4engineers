<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guest = $this->user() === null;

        return [
            'contact' => [$guest ? 'required' : 'nullable', 'array'],
            'contact.name' => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'contact.email' => [$guest ? 'required' : 'nullable', 'email:rfc', 'max:255'],
            'contact.mobile' => [$guest ? 'required' : 'nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'address_id' => ['nullable', 'integer'],
            'shipping_address' => ['required_without:address_id', 'array'],
            'shipping_address.type' => ['nullable', 'in:home,work,other'],
            'shipping_address.full_name' => ['required_without:address_id', 'string', 'max:120'],
            'shipping_address.mobile' => ['required_without:address_id', 'regex:/^[6-9][0-9]{9}$/'],
            'shipping_address.address_line_1' => ['required_without:address_id', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.landmark' => ['nullable', 'string', 'max:150'],
            'shipping_address.city' => ['required_without:address_id', 'string', 'max:100'],
            'shipping_address.state' => ['required_without:address_id', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required_without:address_id', 'regex:/^[1-9][0-9]{5}$/'],
            'shipping_address.country_code' => ['nullable', 'in:IN'],
            'shipping_quote_id' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::in(['cod', 'scanpay', 'razorpay', 'payu', 'cashfree'])],
            'idempotency_key' => ['required', 'string', 'max:100'],
            'use_store_credit' => ['sometimes', 'boolean'],
        ];
    }
}
