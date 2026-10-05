<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookInventory extends Model
{
    use HasFactory;

    protected $table = 'book_inventory';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_backorder_allowed' => 'boolean'];
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function getStatusAttribute(): string
    {
        return $this->available_quantity <= 0 ? 'OUT_OF_STOCK' : ($this->available_quantity <= $this->low_stock_threshold ? 'LOW_STOCK' : 'IN_STOCK');
    }
}
