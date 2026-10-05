<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'is_active'])]
class Tag extends MasterData
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
