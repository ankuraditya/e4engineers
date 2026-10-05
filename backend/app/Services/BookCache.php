<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class BookCache
{
    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember('books:v'.$this->version().':'.$key, 3600, $callback);
    }

    public function flush(): void
    {
        Cache::increment('books:version');
    }

    private function version(): int
    {
        return (int) Cache::rememberForever('books:version', fn () => 1);
    }
}
