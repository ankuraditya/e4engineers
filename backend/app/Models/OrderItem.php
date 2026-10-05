<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = [];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    protected function casts(): array
    {
        return ['authors' => 'array', 'product_snapshot' => 'array', 'unit_price' => 'decimal:2', 'discount_total' => 'decimal:2', 'line_total' => 'decimal:2'];
    }
}
