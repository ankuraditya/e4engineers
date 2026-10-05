<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\SeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Page;
use App\Services\CmsCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SeoController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache) {}

    public function show(Page $page): JsonResponse
    {
        Gate::authorize('seo.view');

        return $this->successResponse($page->seo ? new SeoResource($page->seo->load('ogMedia')) : null);
    }

    public function update(SeoRequest $r, Page $page): JsonResponse
    {
        Gate::authorize('seo.update');
        $seo = $page->seo()->updateOrCreate([], $r->validated());
        $this->cache->forget("page:{$page->slug}");

        return $this->successResponse(new SeoResource($seo->load('ogMedia')), 'SEO updated.');
    }
}
