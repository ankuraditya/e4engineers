<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'context', 'description', 'sort_order', 'is_active'])]
class Category extends MasterData
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
