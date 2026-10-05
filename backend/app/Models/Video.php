<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_date' => 'date', 'published_at' => 'datetime', 'is_featured' => 'boolean'];
    }

    public function thumbnail()
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    public function discipline()
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return $this->video_type === 'youtube' && preg_match('/^[A-Za-z0-9_-]{11}$/', $this->external_video_id ?? '') ? "https://www.youtube-nocookie.com/embed/{$this->external_video_id}" : ($this->video_type === 'vimeo' && ctype_digit($this->external_video_id ?? '') ? "https://player.vimeo.com/video/{$this->external_video_id}" : null);
    }
}
