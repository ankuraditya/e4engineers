<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notice extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['notice_date' => 'date', 'expires_at' => 'datetime', 'published_at' => 'datetime', 'is_featured' => 'boolean', 'is_pinned' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function discipline()
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function featuredMedia()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function attachmentMedia()
    {
        return $this->belongsTo(Media::class, 'attachment_media_id');
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
