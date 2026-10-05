<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class CmsCache
{
    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember("cms:v2:{$key}", config('cms.cache_ttl'), $callback);
    }

    public function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            Cache::forget("cms:v2:{$key}");
        }
    }
}
