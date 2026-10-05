<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class DigitalResourceCache
{
    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember('digital-resources:v'.$this->version().':'.$key, 3600, $callback);
    }

    public function flush(): void
    {
        Cache::increment('digital-resources:version');
    }

    private function version(): int
    {
        return (int) Cache::rememberForever('digital-resources:version', fn () => 1);
    }
}
