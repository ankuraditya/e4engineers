<?php

namespace App\Models;

use Database\Factories\WebsiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    /** @use HasFactory<WebsiteSettingFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function publicationsEnabled(): bool
    {
        return static::query()->where('key', 'publications_enabled')->value('value') !== '0';
    }
}
