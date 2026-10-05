<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['seller_snapshot' => 'array', 'customer_snapshot' => 'array', 'billing_address_snapshot' => 'array', 'shipping_address_snapshot' => 'array', 'presentation_snapshot' => 'array', 'issued_at' => 'datetime', 'pdf_generated_at' => 'datetime', 'voided_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
