<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicationResource;
use App\Models\Publication;
use App\Models\WebsiteSetting;
use App\Services\DigitalAccessService;
use App\Services\PublicationCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicationController extends Controller
{
    use ApiResponse;

    public function __construct(private PublicationCache $cache, private DigitalAccessService $access) {}

    private function query()
    {
        return Publication::query()->where('status', PublicationStatus::Published)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->with(['type', 'discipline', 'category', 'featuredMedia', 'previewMedia', 'contributors.media']);
    }

    public function index(Request $r): JsonResponse
    {
        abort_unless(WebsiteSetting::publicationsEnabled(), 404);
        $d = $r->validate(['search' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:255', 'type' => 'nullable|string|max:255', 'category' => 'nullable|string|max:255', 'access' => 'nullable|in:free,paid', 'featured' => 'nullable|boolean', 'sort' => 'nullable|in:latest,oldest,a-z,featured', 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100']);
        $payload = $this->cache->remember('list:'.hash('sha256', json_encode($d)), function () use ($d, $r) {
            $q = $this->query()->when($d['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%{$v}%")->orWhere('short_description', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%")->orWhere('author_text', 'like', "%{$v}%")->orWhere('editor_text', 'like', "%{$v}%")))->when($d['discipline'] ?? null, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))->when($d['type'] ?? null, fn ($q, $v) => $q->whereHas('type', fn ($x) => $x->where('slug', $v)))->when($d['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($x) => $x->where('slug', $v)))->when($d['access'] ?? null, fn ($q, $v) => $q->where('access_type', $v))->when(array_key_exists('featured', $d), fn ($q) => $q->where('is_featured', (bool) $d['featured']));
            match ($d['sort'] ?? 'latest') {
                'oldest' => $q->orderBy('publication_date'),'a-z' => $q->orderBy('title'),'featured' => $q->orderByDesc('is_featured')->orderBy('featured_order'),default => $q->latest('publication_date')
            };
            $p = $q->paginate($d['per_page'] ?? 12);

            return ['items' => PublicationResource::collection($p->items())->resolve($r), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]];
        });

        return $this->successResponse($payload['items'], 'Publications retrieved.', 200, $payload['meta']);
    }

    public function show(Request $r, string $slug): JsonResponse
    {
        abort_unless(WebsiteSetting::publicationsEnabled(), 404);
        $data = $this->cache->remember('detail:'.$slug, function () use ($slug, $r) {
            $p = $this->query()->with('seo.ogMedia')->where('slug', $slug)->first();
            if (! $p) {
                return null;
            }$related = $this->query()->whereKeyNot($p->id)->where(fn ($q) => $q->where('publication_type_id', $p->publication_type_id)->orWhere('engineering_discipline_id', $p->engineering_discipline_id)->orWhere('category_id', $p->category_id))->limit(4)->get();

            return ['publication' => (new PublicationResource($p))->resolve($r), 'related' => PublicationResource::collection($related)->resolve($r)];
        });
        abort_unless($data, 404);
        $publication = Publication::where('slug', $slug)->firstOrFail();
        $decision = $this->access->decision($r->user(), $publication);
        $data['publication']['access'] = ['type' => $publication->access_type->value, 'requires_login' => $publication->access_type->value !== 'free', 'requires_purchase' => $publication->access_type->value === 'paid', 'has_file' => (bool) $publication->file_media_id, 'has_entitlement' => (bool) $decision['entitlement'], 'can_download' => $decision['allowed'], 'reason' => $decision['reason']];

        return $this->successResponse($data);
    }
}
