<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['unit_price_snapshot' => 'decimal:2'];
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }
}
