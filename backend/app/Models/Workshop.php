<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workshop extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['meeting_url'];

    protected function casts(): array
    {
        return ['meeting_url' => 'encrypted', 'start_at' => 'datetime', 'end_at' => 'datetime', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime', 'published_at' => 'datetime', 'is_featured' => 'boolean', 'fee' => 'decimal:2'];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(WorkshopRegistration::class);
    }
}
