<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\SeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\DigitalResource;
use App\Services\DigitalResourceCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DigitalResourceSeoController extends Controller
{
    use ApiResponse;

    public function __construct(private DigitalResourceCache $cache) {}

    public function show(DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.view');

        return $this->successResponse($resource->seo ? new SeoResource($resource->seo) : null);
    }

    public function update(SeoRequest $request, DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.update');
        $seo = $resource->seo()->updateOrCreate([], $request->validated());
        $this->cache->flush();

        return $this->successResponse(new SeoResource($seo), 'Resource SEO updated.');
    }
}
