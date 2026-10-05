<?php

namespace App\Models;

use App\Enums\DigitalResourceAccessType;
use App\Enums\DigitalResourcePreviewType;
use App\Enums\DigitalResourceStatus;
use Database\Factories\DigitalResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalResource extends Model
{
    /** @use HasFactory<DigitalResourceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['access_type' => DigitalResourceAccessType::class, 'preview_type' => DigitalResourcePreviewType::class, 'status' => DigitalResourceStatus::class, 'price' => 'decimal:2', 'is_featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    public function previewMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_media_id');
    }

    public function fileMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'file_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'digital_resource_tag');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function entitlements(): MorphMany
    {
        return $this->morphMany(DigitalEntitlement::class, 'entitleable');
    }

    public function downloadLogs(): MorphMany
    {
        return $this->morphMany(DigitalDownloadLog::class, 'downloadable');
    }
}
