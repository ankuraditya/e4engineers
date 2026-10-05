<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class PublicationCache
{
    public function remember(string $k, callable $cb): mixed
    {
        return Cache::remember('publications:v'.$this->version().':'.$k, 3600, $cb);
    }

    public function flush(): void
    {
        Cache::increment('publications:version');
    }

    private function version(): int
    {
        return (int) Cache::rememberForever('publications:version', fn () => 1);
    }
}
