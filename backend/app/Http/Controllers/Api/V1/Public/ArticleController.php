<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArticleResource;
use App\Models\Article;
use App\Services\ArticleCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    use ApiResponse;

    public function __construct(private ArticleCache $cache) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:255', 'category' => 'nullable|string|max:255', 'topic' => 'nullable|string|max:255', 'tag' => 'nullable|string|max:255', 'featured' => 'nullable|boolean', 'sort' => 'nullable|in:latest,oldest,a-z,featured', 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100']);
        $payload = $this->cache->remember('list:'.hash('sha256', json_encode($data)), function () use ($data, $request) {
            $q = $this->query()->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")->orWhere('content', 'like', "%{$v}%")->orWhereHas('contributors', fn ($c) => $c->where('name', 'like', "%{$v}%"))))->when($data['discipline'] ?? null, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))->when($data['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($x) => $x->where('slug', $v)))->when($data['topic'] ?? null, fn ($q, $v) => $q->whereHas('topic', fn ($x) => $x->where('slug', $v)))->when($data['tag'] ?? null, fn ($q, $v) => $q->whereHas('tags', fn ($x) => $x->where('slug', $v)))->when(array_key_exists('featured', $data), fn ($q) => $q->where('is_featured', (bool) $data['featured']));
            match ($data['sort'] ?? 'latest') {
                'oldest' => $q->orderBy('published_at'),'a-z' => $q->orderBy('title'),'featured' => $q->orderByDesc('is_featured')->orderBy('featured_order'),default => $q->orderByDesc('published_at')
            };
            $p = $q->paginate($data['per_page'] ?? 12);

            return ['items' => ArticleResource::collection($p->items())->resolve($request), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]];
        });

        return $this->successResponse($payload['items'], 'Articles retrieved.', 200, $payload['meta']);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $data = $this->cache->remember('detail:'.$slug, function () use ($slug, $request) {
            $a = $this->query()->with('seo.ogMedia')->where('slug', $slug)->first();
            if (! $a) {
                return null;
            }
            $published = Article::query()->where('status', ArticleStatus::Published)->whereNotNull('published_at')->where('published_at', '<=', now());
            $previous = (clone $published)->where('published_at', '<', $a->published_at)->latest('published_at')->first(['title', 'slug']);
            $next = (clone $published)->where('published_at', '>', $a->published_at)->oldest('published_at')->first(['title', 'slug']);
            $related = $this->query()->whereKeyNot($a->id)->where(fn ($q) => $q->where('engineering_discipline_id', $a->engineering_discipline_id)->orWhere('topic_id', $a->topic_id))->limit(4)->get();

            return ['article' => (new ArticleResource($a))->resolve($request), 'related' => ArticleResource::collection($related)->resolve($request), 'previous' => $previous?->only(['title', 'slug']), 'next' => $next?->only(['title', 'slug'])];
        });
        abort_unless($data, 404);

        return $this->successResponse($data);
    }

    private function query()
    {
        return Article::query()->where('status', ArticleStatus::Published)->whereNotNull('published_at')->where('published_at', '<=', now())->with(['discipline', 'category', 'topic', 'tags', 'contributors.media', 'featuredMedia']);
    }
}
