<?php

namespace App\Models;

use App\Enums\BookFormat;
use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => BookStatus::class, 'format' => BookFormat::class, 'mrp' => 'decimal:2', 'selling_price' => 'decimal:2', 'is_featured' => 'boolean', 'is_new_arrival' => 'boolean', 'published_at' => 'datetime'];
    }

    public function discipline()
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function disciplines()
    {
        return $this->belongsToMany(EngineeringDiscipline::class, 'book_engineering_disciplines')->orderBy('engineering_disciplines.sort_order');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function cover()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class)->withPivot(['role', 'sort_order'])->orderByPivot('sort_order');
    }

    public function images()
    {
        return $this->hasMany(BookImage::class)->orderBy('sort_order');
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function inventory()
    {
        return $this->hasOne(BookInventory::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
