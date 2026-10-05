<?php

namespace App\Models;

use Database\Factories\SeoMetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    /** @use HasFactory<SeoMetaFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $table = 'seo_meta';

    protected function casts(): array
    {
        return ['structured_data' => 'array'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }
}
