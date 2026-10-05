<?php

namespace App\Models;

use Database\Factories\ContributorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Contributor extends Model
{
    /** @use HasFactory<ContributorFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_active' => 'boolean', 'published_at' => 'datetime'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(EngineeringDiscipline::class)->withPivot(['is_primary', 'sort_order'])->orderByPivot('sort_order');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withPivot(['role', 'is_primary', 'sort_order']);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_contributor')->withPivot(['role', 'is_primary', 'sort_order']);
    }
}
