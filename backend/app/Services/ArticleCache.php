<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class ArticleCache
{
    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember('articles:v'.$this->version().':'.$key, 3600, $callback);
    }

    public function flush(): void
    {
        Cache::increment('articles:version');
    }

    private function version(): int
    {
        return (int) Cache::rememberForever('articles:version', fn () => 1);
    }
}
