<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function photo()
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function books()
    {
        return $this->belongsToMany(Book::class)->withPivot(['role', 'sort_order'])->orderByPivot('sort_order');
    }
}
