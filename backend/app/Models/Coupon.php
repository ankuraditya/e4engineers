<?php

namespace App\Models;

use App\Enums\CouponDiscountType;
use App\Enums\CouponScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['discount_type' => CouponDiscountType::class, 'applies_to' => CouponScope::class, 'discount_value' => 'decimal:2', 'minimum_subtotal' => 'decimal:2', 'maximum_discount' => 'decimal:2', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'coupon_books');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'coupon_book_categories');
    }

    public function disciplines()
    {
        return $this->belongsToMany(EngineeringDiscipline::class, 'coupon_engineering_disciplines');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $coupon): void {
            $coupon->code = strtoupper(trim($coupon->code));
        });
    }
}
