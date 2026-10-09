<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['guest_access_token_hash', 'guest_access_token_encrypted', 'idempotency_key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class, 'payment_status' => PaymentStatus::class,
            'shipping_status' => ShippingStatus::class, 'coupon_snapshot' => 'array',
            'shipping_snapshot' => 'array', 'pickup_ready_at' => 'datetime', 'picked_up_at' => 'datetime', 'placed_at' => 'datetime', 'cancelled_at' => 'datetime', 'inventory_restored_at' => 'datetime', 'inventory_reserved_at' => 'datetime', 'inventory_finalized_at' => 'datetime', 'inventory_released_at' => 'datetime',
            'guest_access_token_encrypted' => 'encrypted',
            'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'shipping_total' => 'decimal:2',
            'cod_charge' => 'decimal:2', 'tax_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'store_credit_total' => 'decimal:2',
        ];
    }
}
