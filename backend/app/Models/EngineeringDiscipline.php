<?php

namespace App\Models;

use Database\Factories\EngineeringDisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'short_name', 'description', 'icon', 'image', 'sort_order', 'is_active'])]
class EngineeringDiscipline extends MasterData
{
    /** @use HasFactory<EngineeringDisciplineFactory> */
    use HasFactory;

    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(Contributor::class)->withPivot(['is_primary', 'sort_order']);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
