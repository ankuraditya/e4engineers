<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Publications\ManagePublication;
use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Publications\StorePublicationRequest;
use App\Http\Requests\Api\V1\Publications\UpdatePublicationRequest;
use App\Http\Resources\Api\V1\PublicationAdminResource;
use App\Models\Publication;
use App\Services\PublicationCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PublicationController extends Controller
{
    use ApiResponse;

    public function __construct(private ManagePublication $a, private PublicationCache $cache) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('publications.view');
        $p = Publication::query()->when($r->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))->when($r->status, fn ($q, $v) => $q->where('status', $v))->when($r->type, fn ($q, $v) => $q->where('publication_type_id', $v))->when($r->discipline, fn ($q, $v) => $q->where('engineering_discipline_id', $v))->when($r->access, fn ($q, $v) => $q->where('access_type', $v))->when($r->filled('featured'), fn ($q) => $q->where('is_featured', $r->boolean('featured')))->with($this->a->relations())->latest()->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(PublicationAdminResource::collection($p), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]);
    }

    public function store(StorePublicationRequest $r): JsonResponse
    {
        Gate::authorize('publications.create');

        return $this->successResponse(new PublicationAdminResource($this->a->create($r->validated(), $r->user()->id)), 'Publication created.', 201);
    }

    public function show(Publication $publication): JsonResponse
    {
        Gate::authorize('publications.view');

        return $this->successResponse(new PublicationAdminResource($publication->load($this->a->relations())));
    }

    public function update(UpdatePublicationRequest $r, Publication $publication): JsonResponse
    {
        Gate::authorize('publications.update');

        return $this->successResponse(new PublicationAdminResource($this->a->update($publication, $r->validated(), $r->user()->id)), 'Publication updated.');
    }

    public function status(Request $r, Publication $publication): JsonResponse
    {
        Gate::authorize('publications.publish');
        $d = $r->validate(['status' => ['required', Rule::enum(PublicationStatus::class)], 'published_at' => 'nullable|date']);

        return $this->successResponse(new PublicationAdminResource($this->a->update($publication, $d, $r->user()->id)), 'Status updated.');
    }

    public function featured(Request $r, Publication $publication): JsonResponse
    {
        Gate::authorize('publications.feature');
        $d = $r->validate(['is_featured' => 'required|boolean', 'featured_order' => 'nullable|integer|min:0']);

        return $this->successResponse(new PublicationAdminResource($this->a->update($publication, $d, $r->user()->id)), 'Featured state updated.');
    }

    public function destroy(Publication $publication): JsonResponse
    {
        Gate::authorize('publications.delete');
        $publication->delete();
        $this->cache->flush();

        return $this->successResponse(null,'Publication deleted.');
    }
}
