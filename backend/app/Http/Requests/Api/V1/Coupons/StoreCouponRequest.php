<?php

namespace App\Http\Requests\Api\V1\Coupons;

use App\Enums\CouponDiscountType;
use App\Enums\CouponScope;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends ApiRequest
{
    public function rules(): array
    {
        return $this->rulesFor();
    }

    protected function rulesFor(?int $id = null, bool $partial = false): array
    {
        $r = $partial ? 'sometimes' : 'required';

        return ['code' => [$r, 'string', 'max:64', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons')->ignore($id)], 'name' => [$r, 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'], 'discount_type' => [$r, Rule::enum(CouponDiscountType::class)], 'discount_value' => [$r, 'numeric', 'gt:0'], 'minimum_subtotal' => ['nullable', 'numeric', 'min:0'], 'maximum_discount' => ['nullable', 'numeric', 'gt:0'], 'starts_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after:starts_at'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'per_customer_limit' => ['nullable', 'integer', 'min:1'], 'is_active' => ['sometimes', 'boolean'], 'applies_to' => [$r, Rule::enum(CouponScope::class)], 'book_ids' => ['sometimes', 'array'], 'book_ids.*' => ['integer', 'distinct', 'exists:books,id'], 'category_ids' => ['sometimes', 'array'], 'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'], 'discipline_ids' => ['sometimes', 'array'], 'discipline_ids.*' => ['integer', 'distinct', 'exists:engineering_disciplines,id']];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->code))]);
        }
    }

    public function after(): array
    {
        return [function ($v) {
            $type = $this->input('discount_type');
            if ($type === 'percentage' && (float) $this->input('discount_value', 0) > 100) {
                $v->errors()->add('discount_value', 'Percentage discount may not exceed 100.');
            }if ($type === 'fixed' && $this->filled('maximum_discount')) {
                $v->errors()->add('maximum_discount', 'Maximum discount is only supported for percentage coupons.');
            }if ($this->filled('usage_limit') && $this->filled('per_customer_limit') && (int) $this->per_customer_limit > (int) $this->usage_limit) {
                $v->errors()->add('per_customer_limit', 'Per-customer limit may not exceed global usage limit.');
            }$scope = $this->input('applies_to');
            $map = ['specific_books' => 'book_ids', 'book_categories' => 'category_ids', 'engineering_disciplines' => 'discipline_ids'];
            if (isset($map[$scope]) && count($this->input($map[$scope], [])) === 0) {
                $v->errors()->add($map[$scope], 'At least one eligible record is required for this scope.');
            }foreach ($map as $s => $field) {
                if ($scope !== $s && $this->filled($field)) {
                    $v->errors()->add($field, 'Restriction IDs must match the selected scope.');
                }
            }
        }];
    }
}
