<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContributorResource;
use App\Models\Contributor;
use App\Services\ContributorCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContributorController extends Controller
{
    use ApiResponse;

    public function __construct(private ContributorCache $cache) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'discipline' => ['nullable', 'string', 'max:255'], 'featured' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $key = 'list:'.hash('sha256', json_encode($data));
        $payload = $this->cache->remember($key, function () use ($data, $request) {
            $items = Contributor::query()->where('is_active', true)->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('name', 'like', "%{$v}%")->orWhere('designation', 'like', "%{$v}%")->orWhere('qualification', 'like', "%{$v}%")->orWhere('expertise_summary', 'like', "%{$v}%")))->when($data['discipline'] ?? null, fn ($q, $v) => $q->whereHas('disciplines', fn ($d) => $d->where('slug', $v)))->when(array_key_exists('featured', $data), fn ($q) => $q->where('is_featured', (bool) $data['featured']))->with(['media', 'disciplines'])->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name')->paginate($data['per_page'] ?? 12);

            return ['items' => ContributorResource::collection($items->items())->resolve($request), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]];
        });

        return $this->successResponse($payload['items'], 'Contributors retrieved.', 200, $payload['meta']);
    }

    public function show(string $slug): JsonResponse
    {
        $item = $this->cache->remember('detail:'.$slug, function () use ($slug) {
            $contributor = Contributor::query()->where('slug', $slug)->where('is_active', true)->with(['media', 'disciplines', 'seo.ogMedia'])->first();

            return $contributor ? (new ContributorResource($contributor))->resolve(request()) : null;
        });
        abort_unless($item, 404);

        return $this->successResponse($item);
    }
}
