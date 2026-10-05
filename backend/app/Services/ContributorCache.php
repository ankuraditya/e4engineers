<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class ContributorCache
{
    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember('contributors:v'.$this->version().':'.$key, 3600, $callback);
    }

    public function flush(): void
    {
        Cache::increment('contributors:version');
    }

    private function version(): int
    {
        return (int) Cache::rememberForever('contributors:version', fn () => 1);
    }
}
