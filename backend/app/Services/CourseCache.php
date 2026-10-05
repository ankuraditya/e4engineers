<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class CourseCache
{
    public function remember(string $key, callable $cb): mixed
    {
        return Cache::remember('courses:v'.$this->version().':'.$key, 3600, $cb);
    }

    public function flush(): void
    {
        Cache::increment('courses:version');
    }

    private function version(): int
    {
        return (int) Cache::rememberForever('courses:version', fn () => 1);
    }
}
