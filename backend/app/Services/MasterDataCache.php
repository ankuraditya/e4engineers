<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

final class MasterDataCache
{
    public function remember(string $type, Closure $callback): mixed
    {
        return Cache::remember("master-data.public.{$type}", now()->addHour(), $callback);
    }

    public function forget(string $type): void
    {
        Cache::forget("master-data.public.{$type}");
    }
}
