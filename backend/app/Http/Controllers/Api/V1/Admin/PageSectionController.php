<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\PageSectionRequest;
use App\Http\Requests\Api\V1\Cms\ReorderRequest;
use App\Http\Resources\Api\V1\PageSectionResource;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\CmsCache;
use App\Services\HtmlSanitizer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PageSectionController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache, private HtmlSanitizer $sanitizer) {}

    public function index(Page $page): JsonResponse
    {
        Gate::authorize('pages.view');

        return $this->successResponse(PageSectionResource::collection($page->sections()->with('media')->get()));
    }

    public function store(PageSectionRequest $request, Page $page): JsonResponse
    {
        Gate::authorize('pages.update');
        $data = $request->validated();
        $data['content'] = $this->sanitizer->clean($data['content'] ?? null);
        $section = $page->sections()->create($data);
        $this->cache->forget("page:{$page->slug}");

        return $this->successResponse(new PageSectionResource($section), 'Section created.', 201);
    }

    public function update(PageSectionRequest $request, PageSection $section): JsonResponse
    {
        Gate::authorize('pages.update');
        $data = $request->validated();
        if (array_key_exists('content', $data)) {
            $data['content'] = $this->sanitizer->clean($data['content']);
        }$section->update($data);
        $this->cache->forget("page:{$section->page->slug}");

        return $this->successResponse(new PageSectionResource($section->refresh()));
    }

    public function destroy(PageSection $section): JsonResponse
    {
        Gate::authorize('pages.update');
        $slug = $section->page->slug;
        $section->delete();
        $this->cache->forget("page:{$slug}");

        return $this->successResponse(null, 'Section deleted.');
    }

    public function reorder(ReorderRequest $request, Page $page): JsonResponse
    {
        Gate::authorize('pages.update');
        DB::transaction(fn () => collect($request->validated('items'))->each(fn ($i) => $page->sections()->whereKey($i['id'])->update(['sort_order' => $i['sort_order']])));
        $this->cache->forget("page:{$page->slug}");

        return $this->successResponse(null, 'Sections reordered.');
    }
}
