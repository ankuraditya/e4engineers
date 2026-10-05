<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Contributors\ManageContributor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Contributors\ReorderContributorsRequest;
use App\Http\Requests\Api\V1\Contributors\StoreContributorRequest;
use App\Http\Requests\Api\V1\Contributors\UpdateContributorRequest;
use App\Http\Resources\Api\V1\ContributorAdminResource;
use App\Models\Contributor;
use App\Services\ContributorCache;
use App\Services\HtmlSanitizer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ContributorController extends Controller
{
    use ApiResponse;

    public function __construct(private ManageContributor $action, private ContributorCache $cache, private HtmlSanitizer $sanitizer) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('contributors.view');
        $items = Contributor::query()->when($r->search, fn ($q, $v) => $q->where(fn ($x) => $x->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))->when($r->discipline, fn ($q, $v) => $q->whereHas('disciplines', fn ($d) => $d->where('slug', $v)))->when($r->filled('active'), fn ($q) => $q->where('is_active', $r->boolean('active')))->when($r->filled('featured'), fn ($q) => $q->where('is_featured', $r->boolean('featured')))->with(['media', 'disciplines'])->orderBy($r->input('sort', 'sort_order') === 'name' ? 'name' : 'sort_order')->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(ContributorAdminResource::collection($items), meta: ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]);
    }

    public function store(StoreContributorRequest $r): JsonResponse
    {
        Gate::authorize('contributors.create');
        $data = $this->clean($r->validated());
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return $this->successResponse(new ContributorAdminResource($this->action->create($data, $r->user()->id)), 'Contributor created.', 201);
    }

    public function show(Contributor $contributor): JsonResponse
    {
        Gate::authorize('contributors.view');

        return $this->successResponse(new ContributorAdminResource($contributor->load(['media', 'disciplines', 'seo.ogMedia'])));
    }

    public function update(UpdateContributorRequest $r, Contributor $contributor): JsonResponse
    {
        Gate::authorize('contributors.update');
        $data = $this->clean($r->validated());
        if (! array_key_exists('slug', $data)) {
            unset($data['slug']);
        }

return $this->successResponse(new ContributorAdminResource($this->action->update($contributor, $data, $r->user()->id)));
    }

    public function status(Request $r, Contributor $contributor): JsonResponse
    {
        Gate::authorize('contributors.publish');
        $data = $r->validate(['is_active' => 'required|boolean']);
        $data['published_at'] = $data['is_active'] ? ($contributor->published_at ?? now()) : $contributor->published_at;

        return $this->successResponse(new ContributorAdminResource($this->action->update($contributor, $data, $r->user()->id)));
    }

    public function featured(Request $r, Contributor $contributor): JsonResponse
    {
        Gate::authorize('contributors.feature');

        return $this->successResponse(new ContributorAdminResource($this->action->update($contributor, $r->validate(['is_featured' => 'required|boolean']), $r->user()->id)));
    }

    public function reorder(ReorderContributorsRequest $r): JsonResponse
    {
        Gate::authorize('contributors.reorder');
        $this->action->reorder($r->validated('items'));

        return $this->successResponse(null, 'Contributors reordered.');
    }

    public function destroy(Contributor $contributor): JsonResponse
    {
        Gate::authorize('contributors.delete');
        $contributor->update(['is_active' => false]);
        $this->cache->flush();

        return $this->successResponse(null, 'Contributor deactivated.');
    }

    private function clean(array $data): array
    {
        if (array_key_exists('biography',$data)) {
            $data['biography'] = $this->sanitizer->clean($data['biography']);
        }

return $data;
    }
}
