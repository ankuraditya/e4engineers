<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class CouponResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'discount_type' => $this->discount_type->value, 'discount_value' => $this->discount_value, 'minimum_subtotal' => $this->minimum_subtotal, 'maximum_discount' => $this->maximum_discount, 'starts_at' => $this->starts_at, 'expires_at' => $this->expires_at, 'usage_limit' => $this->usage_limit, 'per_customer_limit' => $this->per_customer_limit, 'usage_count' => $this->whenCounted('usages'), 'is_active' => $this->is_active, 'applies_to' => $this->applies_to->value, 'book_ids' => $this->whenLoaded('books', fn () => $this->books->pluck('id')), 'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')), 'discipline_ids' => $this->whenLoaded('disciplines', fn () => $this->disciplines->pluck('id')), 'created_at' => $this->created_at, 'updated_at' => $this->updated_at];
    }
}
