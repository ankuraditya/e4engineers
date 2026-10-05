<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
