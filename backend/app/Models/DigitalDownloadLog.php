<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalDownloadLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function downloadable(): MorphTo
    {
        return $this->morphTo();
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(DigitalEntitlement::class);
    }
}
