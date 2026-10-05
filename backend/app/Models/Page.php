<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageType;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['page_type' => PageType::class, 'status' => PageStatus::class, 'is_system' => 'boolean', 'published_at' => 'datetime'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
