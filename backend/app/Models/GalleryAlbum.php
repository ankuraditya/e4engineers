<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryAlbum extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'published_at' => 'datetime', 'is_featured' => 'boolean'];
    }

    public function images()
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order');
    }

    public function cover()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function discipline()
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
