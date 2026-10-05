<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payload' => 'array'];
    }

    public function attempt()
    {
        return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
