<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\ReorderRequest;
use App\Http\Requests\Api\V1\Cms\SocialLinkRequest;
use App\Http\Resources\Api\V1\SocialLinkResource;
use App\Models\SocialLink;
use App\Services\CmsCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SocialLinkController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache) {}

    public function index(): JsonResponse
    {
        Gate::authorize('social-links.view');

        return $this->successResponse(SocialLinkResource::collection(SocialLink::orderBy('sort_order')->get()));
    }

    public function store(SocialLinkRequest $r): JsonResponse
    {
        Gate::authorize('social-links.create');
        $m = SocialLink::create($r->validated());
        $this->cache->forget('social-links');

        return $this->successResponse(new SocialLinkResource($m), 'Social link created.', 201);
    }

    public function update(SocialLinkRequest $r, SocialLink $socialLink): JsonResponse
    {
        Gate::authorize('social-links.update');
        $socialLink->update($r->validated());
        $this->cache->forget('social-links');

        return $this->successResponse(new SocialLinkResource($socialLink->refresh()));
    }

    public function status(Request $r, SocialLink $socialLink): JsonResponse
    {
        Gate::authorize('social-links.update');
        $socialLink->update($r->validate(['is_active' => 'required|boolean']));
        $this->cache->forget('social-links');

        return $this->successResponse(new SocialLinkResource($socialLink));
    }

    public function destroy(SocialLink $socialLink): JsonResponse
    {
        Gate::authorize('social-links.delete');
        $socialLink->delete();
        $this->cache->forget('social-links');

        return $this->successResponse();
    }

    public function reorder(ReorderRequest $r): JsonResponse
    {
        Gate::authorize('social-links.reorder');
        DB::transaction(fn () => collect($r->validated('items'))->each(fn ($i) => SocialLink::whereKey($i['id'])->update(['sort_order' => $i['sort_order']])));
        $this->cache->forget('social-links');

        return $this->successResponse();
    }
}
