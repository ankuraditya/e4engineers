<?php

namespace App\Models;

use App\Enums\PublicationAccessType;
use App\Enums\PublicationPreviewType;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publication extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => PublicationStatus::class, 'access_type' => PublicationAccessType::class, 'preview_type' => PublicationPreviewType::class, 'is_featured' => 'boolean', 'price' => 'decimal:2', 'publication_date' => 'date', 'published_at' => 'datetime'];
    }

    public function type()
    {
        return $this->belongsTo(PublicationType::class, 'publication_type_id');
    }

    public function discipline()
    {
        return $this->belongsTo(EngineeringDiscipline::class, 'engineering_discipline_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function featuredMedia()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function previewMedia()
    {
        return $this->belongsTo(Media::class, 'preview_media_id');
    }

    public function fileMedia()
    {
        return $this->belongsTo(Media::class, 'file_media_id');
    }

    public function entitlements()
    {
        return $this->morphMany(DigitalEntitlement::class, 'entitleable');
    }

    public function downloadLogs()
    {
        return $this->morphMany(DigitalDownloadLog::class, 'downloadable');
    }

    public function contributors()
    {
        return $this->belongsToMany(Contributor::class, 'publication_contributor')->withPivot(['role', 'sort_order'])->orderByPivot('sort_order');
    }

    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
