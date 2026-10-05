<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['discount_amount' => 'decimal:2', 'used_at' => 'datetime'];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
