<?php

namespace App\Http\Requests\Api\V1\Coupons;

class UpdateCouponRequest extends StoreCouponRequest
{
    public function rules(): array
    {
        return $this->rulesFor((int) $this->route('coupon')->id, true);
    }
}
