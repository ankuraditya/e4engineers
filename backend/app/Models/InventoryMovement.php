<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => InventoryMovementType::class, 'created_at' => 'datetime'];
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
