<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\DigitalResources\ManageDigitalResource;
use App\Enums\DigitalResourceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DigitalResources\StoreDigitalResourceRequest;
use App\Http\Requests\Api\V1\DigitalResources\UpdateDigitalResourceRequest;
use App\Http\Resources\Api\V1\DigitalResourceAdminResource;
use App\Models\DigitalResource;
use App\Services\DigitalResourceCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DigitalResourceController extends Controller
{
    use ApiResponse;

    public function __construct(private ManageDigitalResource $action, private DigitalResourceCache $cache) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('resources.view');
        $page = DigitalResource::query()->when($request->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))->when($request->status, fn ($q, $v) => $q->where('status', $v))->when($request->type, fn ($q, $v) => $q->where('resource_type_id', $v))->when($request->discipline, fn ($q, $v) => $q->where('engineering_discipline_id', $v))->when($request->category, fn ($q, $v) => $q->where('category_id', $v))->when($request->topic, fn ($q, $v) => $q->where('topic_id', $v))->when($request->access, fn ($q, $v) => $q->where('access_type', $v))->with($this->action->relations())->latest()->paginate(min((int) $request->input('per_page', 20), 100));

        return $this->successResponse(DigitalResourceAdminResource::collection($page), meta: ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]);
    }

    public function store(StoreDigitalResourceRequest $request): JsonResponse
    {
        Gate::authorize('resources.create');
        if ($request->filled('file_media_id')) {
            Gate::authorize('resources.file.manage');
        }
        if ($request->filled('preview_type') || $request->filled('preview_content') || $request->filled('preview_media_id')) {
            Gate::authorize('resources.preview.manage');
        }

        return $this->successResponse(new DigitalResourceAdminResource($this->action->create($request->validated(), $request->user()->id)), 'Resource created.', 201);
    }

    public function show(DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.view');

        return $this->successResponse(new DigitalResourceAdminResource($resource->load($this->action->relations())));
    }

    public function update(UpdateDigitalResourceRequest $request, DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.update');
        if ($request->has('file_media_id')) {
            Gate::authorize('resources.file.manage');
        }
        if ($request->hasAny(['preview_type', 'preview_content', 'preview_media_id'])) {
            Gate::authorize('resources.preview.manage');
        }

        return $this->successResponse(new DigitalResourceAdminResource($this->action->update($resource, $request->validated(), $request->user()->id)), 'Resource updated.');
    }

    public function status(Request $request, DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.publish');
        $data = $request->validate(['status' => ['required', Rule::enum(DigitalResourceStatus::class)], 'published_at' => 'nullable|date']);

        return $this->successResponse(new DigitalResourceAdminResource($this->action->update($resource, $data, $request->user()->id)), 'Status updated.');
    }

    public function featured(Request $request, DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.feature');
        $data = $request->validate(['is_featured' => 'required|boolean', 'featured_order' => 'nullable|integer|min:0']);

        return $this->successResponse(new DigitalResourceAdminResource($this->action->update($resource, $data, $request->user()->id)), 'Featured state updated.');
    }

    public function destroy(DigitalResource $resource): JsonResponse
    {
        Gate::authorize('resources.delete');
        $resource->delete();
        $this->cache->flush();

        return $this->successResponse(null, 'Resource deleted.');
    }
}
