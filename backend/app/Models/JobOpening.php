<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOpening extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['application_deadline' => 'date', 'published_at' => 'datetime', 'is_featured' => 'boolean'];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CareerApplication::class);
    }
}
