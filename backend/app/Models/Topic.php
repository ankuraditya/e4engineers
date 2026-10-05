<?php

namespace App\Models;

use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['engineering_discipline_id', 'parent_id', 'name', 'slug', 'description', 'sort_order', 'is_active'])]
class Topic extends MasterData
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory;

    public function engineeringDiscipline(): BelongsTo
    {
        return $this->belongsTo(EngineeringDiscipline::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
