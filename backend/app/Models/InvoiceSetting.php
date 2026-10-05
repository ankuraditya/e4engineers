<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['show_gst_columns' => 'boolean', 'show_hsn' => 'boolean', 'show_discount' => 'boolean', 'show_shipping' => 'boolean', 'show_payment_method' => 'boolean', 'show_payment_reference' => 'boolean'];
    }

    public static function current(): self
    {
        $settings = self::firstOrCreate([], ['business_name' => 'E4ENGINEERS', 'display_name' => 'E4ENGINEERS']);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }
}
