<?php

namespace App\Services\Shipping;

use App\Models\Cart;
use App\Models\ShippingSetting;

class PackageCalculator
{
    public function calculate(Cart $cart, string $declaredValue): array
    {
        $settings = ShippingSetting::current();
        $cart->loadMissing('items.book');
        $weight = 0;
        foreach ($cart->items as $item) {
            if (! $item->book?->shipping_enabled) {
                continue;
            }$weight += ($item->book->weight_grams ?: $settings->default_package_weight_grams) * $item->quantity;
        }

return ['weight_grams' => max($weight, $settings->default_package_weight_grams), 'weight_kg' => number_format(max($weight, $settings->default_package_weight_grams) / 1000, 3, '.', ''), 'length_cm' => $settings->default_length_cm, 'width_cm' => $settings->default_width_cm, 'height_cm' => $settings->default_height_cm, 'declared_value' => $declaredValue];
    }
}
