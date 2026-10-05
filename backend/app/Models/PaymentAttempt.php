<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentAttempt extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['proof_path'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'client_action' => 'array', 'failure' => 'array', 'expires_at' => 'datetime', 'proof_uploaded_at' => 'datetime', 'proof_reviewed_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function provider()
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
