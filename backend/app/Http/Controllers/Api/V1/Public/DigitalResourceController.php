<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\DigitalResourceAccessType;
use App\Enums\DigitalResourceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DigitalResourceResource;
use App\Models\DigitalResource;
use App\Services\DigitalAccessService;
use App\Services\DigitalResourceCache;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DigitalResourceController extends Controller
{
    use ApiResponse;

    public function __construct(private DigitalResourceCache $cache, private DigitalAccessService $access) {}

    private function query(): Builder
    {
        return DigitalResource::query()->where('status', DigitalResourceStatus::Published)->with(['type', 'discipline', 'category', 'topic', 'tags', 'thumbnail', 'previewMedia']);
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->input('access') === 'login-required') {
            $request->merge(['access' => 'login_required']);
        }
        $data = $request->validate(['search' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:100', 'type' => 'nullable|string|max:100', 'category' => 'nullable|string|max:100', 'topic' => 'nullable|string|max:100', 'tag' => 'nullable|string|max:100', 'access' => ['nullable', Rule::enum(DigitalResourceAccessType::class)], 'featured' => 'nullable|boolean', 'sort' => ['nullable', Rule::in(['latest', 'oldest', 'a-z', 'featured'])], 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100']);
        $payload = $this->cache->remember('list:'.md5(json_encode($data)), function () use ($data, $request): array {
            $query = $this->query()
                ->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('title', 'like', "%{$v}%")->orWhere('short_description', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%")->orWhereHas('type', fn ($x) => $x->where('name', 'like', "%{$v}%"))->orWhereHas('discipline', fn ($x) => $x->where('name', 'like', "%{$v}%"))->orWhereHas('topic', fn ($x) => $x->where('name', 'like', "%{$v}%"))->orWhereHas('tags', fn ($x) => $x->where('name', 'like', "%{$v}%"))))
                ->when($data['discipline'] ?? null, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))
                ->when($data['type'] ?? null, fn ($q, $v) => $q->whereHas('type', fn ($x) => $x->where('slug', $v)))
                ->when($data['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($x) => $x->where('slug', $v)))
                ->when($data['topic'] ?? null, fn ($q, $v) => $q->whereHas('topic', fn ($x) => $x->where('slug', $v)))
                ->when($data['tag'] ?? null, fn ($q, $v) => $q->whereHas('tags', fn ($x) => $x->where('slug', $v)))
                ->when($data['access'] ?? null, fn ($q, $v) => $q->where('access_type', $v))
                ->when(array_key_exists('featured', $data), fn ($q) => $q->where('is_featured', $data['featured']));
            match ($data['sort'] ?? 'latest') {
                'oldest' => $query->oldest('published_at'), 'a-z' => $query->orderBy('title'), 'featured' => $query->orderByDesc('is_featured')->orderBy('featured_order'), default => $query->latest('published_at')
            };
            $page = $query->paginate($data['per_page'] ?? 12);

            return ['items' => DigitalResourceResource::collection($page->items())->resolve($request), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]];
        });

        return $this->successResponse($payload['items'], 'Resources retrieved.', 200, $payload['meta']);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $data = $this->cache->remember('detail:'.$slug, function () use ($slug, $request): ?array {
            $resource = $this->query()->with('seo.ogMedia')->where('slug', $slug)->first();
            if (! $resource) {
                return null;
            }
            $related = $this->query()->whereKeyNot($resource->id)->where(fn ($q) => $q->where('engineering_discipline_id', $resource->engineering_discipline_id)->orWhere('resource_type_id', $resource->resource_type_id)->orWhere('topic_id', $resource->topic_id))->limit(4)->get();

            return ['resource' => (new DigitalResourceResource($resource))->resolve($request), 'related' => DigitalResourceResource::collection($related)->resolve($request)];
        });
        abort_unless($data, 404);
        $resource = DigitalResource::where('slug', $slug)->firstOrFail();
        $decision = $this->access->decision($request->user(), $resource);
        $data['resource']['access'] += ['has_entitlement' => (bool) $decision['entitlement'], 'can_download' => $decision['allowed'], 'reason' => $decision['reason']];

        return $this->successResponse($data);
    }
}
