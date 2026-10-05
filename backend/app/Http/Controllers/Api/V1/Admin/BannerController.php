<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\BannerRequest;
use App\Http\Requests\Api\V1\Cms\ReorderRequest;
use App\Http\Resources\Api\V1\BannerResource;
use App\Models\Banner;
use App\Services\CmsCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BannerController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache) {}

    private function clear(): void
    {
        $this->cache->forget('banners:all', ...collect(config('cms.banner_placements'))->map(fn ($p) => "banners:{$p}")->all());
    }

    public function index(): JsonResponse
    {
        Gate::authorize('banners.view');

        return $this->successResponse(BannerResource::collection(Banner::with(['desktopMedia', 'mobileMedia'])->orderBy('sort_order')->get()));
    }

    public function store(BannerRequest $r): JsonResponse
    {
        Gate::authorize('banners.create');
        $m = Banner::create($r->validated());
        $this->clear();

        return $this->successResponse(new BannerResource($m->load(['desktopMedia', 'mobileMedia'])), 'Banner created.', 201);
    }

    public function show(Banner $banner): JsonResponse
    {
        Gate::authorize('banners.view');

        return $this->successResponse(new BannerResource($banner->load(['desktopMedia', 'mobileMedia'])));
    }

    public function update(BannerRequest $r, Banner $banner): JsonResponse
    {
        Gate::authorize('banners.update');
        $banner->update($r->validated());
        $this->clear();

        return $this->successResponse(new BannerResource($banner->refresh()->load(['desktopMedia', 'mobileMedia'])));
    }

    public function status(Request $r, Banner $banner): JsonResponse
    {
        Gate::authorize('banners.update');
        $banner->update($r->validate(['is_active' => 'required|boolean']));
        $this->clear();

        return $this->successResponse(new BannerResource($banner));
    }

    public function destroy(Banner $banner): JsonResponse
    {
        Gate::authorize('banners.delete');
        $banner->delete();
        $this->clear();

        return $this->successResponse();
    }

    public function reorder(ReorderRequest $r): JsonResponse
    {
        Gate::authorize('banners.reorder');
        DB::transaction(fn () => collect($r->validated('items'))->each(fn ($i) => Banner::whereKey($i['id'])->update(['sort_order' => $i['sort_order']])));
        $this->clear();

        return $this->successResponse();
    }
}
