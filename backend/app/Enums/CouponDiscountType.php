<?php

namespace App\Enums;

enum CouponDiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
