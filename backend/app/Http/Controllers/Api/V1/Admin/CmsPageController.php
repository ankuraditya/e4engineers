<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\PageRequest;
use App\Http\Resources\Api\V1\PageResource;
use App\Models\Page;
use App\Services\CmsCache;
use App\Services\HtmlSanitizer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CmsPageController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache, private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('pages.view');
        $pages = Page::query()->when($request->status, fn ($q, $v) => $q->where('status', $v))->orderBy('title')->paginate(min((int) $request->input('per_page', 20), 100));

        return $this->successResponse(PageResource::collection($pages), meta: ['current_page' => $pages->currentPage(), 'total' => $pages->total()]);
    }

    public function store(PageRequest $request): JsonResponse
    {
        Gate::authorize('pages.create');
        $data = $this->data($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $page = Page::create($data);
        $this->cache->forget("page:{$page->slug}");

        return $this->successResponse(new PageResource($page), 'Page created.', 201);
    }

    public function show(Page $page): JsonResponse
    {
        Gate::authorize('pages.view');

        return $this->successResponse(new PageResource($page->load(['sections.media', 'seo.ogMedia'])));
    }

    public function update(PageRequest $request, Page $page): JsonResponse
    {
        Gate::authorize('pages.update');
        $old = $page->slug;
        $data = $this->data($request);
        if ($page->is_system) {
            unset($data['slug']);
        } $data['updated_by'] = $request->user()->id;
        $page->update($data);
        $this->cache->forget("page:{$old}", "page:{$page->slug}");

        return $this->successResponse(new PageResource($page->refresh()));
    }

    public function status(Request $request, Page $page): JsonResponse
    {
        Gate::authorize('pages.publish');
        $value = $request->validate(['status' => ['required', 'in:draft,published,archived']])['status'];
        $page->update(['status' => $value, 'published_at' => $value === PageStatus::Published->value ? ($page->published_at ?? now()) : $page->published_at, 'updated_by' => $request->user()->id]);
        $this->cache->forget("page:{$page->slug}");

        return $this->successResponse(new PageResource($page->refresh()));
    }

    public function destroy(Page $page): JsonResponse
    {
        Gate::authorize('pages.delete');
        abort_if($page->is_system, 409, 'System pages cannot be deleted.');
        $slug = $page->slug;
        $page->delete();
        $this->cache->forget("page:{$slug}");

        return $this->successResponse(null, 'Page deleted.');
    }

    private function data(PageRequest $request): array
    {
        $data = $request->validated();
        if (array_key_exists('content', $data)) {
            $data['content'] = $this->sanitizer->clean($data['content']);
        }

return $data;
    }
}
