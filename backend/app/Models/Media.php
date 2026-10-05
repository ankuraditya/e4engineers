<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        if ($this->disk !== 'public') {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
