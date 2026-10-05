<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Models\Course;
use App\Services\CourseCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(private CourseCache $cache) {}

    private function query()
    {
        return Course::query()->where('status', CourseStatus::Published)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->with(['discipline', 'level', 'featuredMedia', 'contributors.media', 'outcomes', 'modules.lessons', 'faqs']);
    }

    public function index(Request $r): JsonResponse
    {
        $d = $r->validate(['search' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:255', 'level' => 'nullable|string|max:255', 'mode' => 'nullable|string|max:50', 'featured' => 'nullable|boolean', 'sort' => 'nullable|in:latest,a-z,featured', 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100']);
        $payload = $this->cache->remember('list:'.hash('sha256', json_encode($d)), function () use ($d, $r) {
            $q = $this->query()->when($d['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%{$v}%")->orWhere('short_description', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%")->orWhereHas('contributors', fn ($c) => $c->where('name', 'like', "%{$v}%"))))->when($d['discipline'] ?? null, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))->when($d['level'] ?? null, fn ($q, $v) => $q->whereHas('level', fn ($x) => $x->where('slug', $v)))->when($d['mode'] ?? null, fn ($q, $v) => $q->where('mode', $v))->when(array_key_exists('featured', $d), fn ($q) => $q->where('is_featured', (bool) $d['featured']));
            match ($d['sort'] ?? 'latest') {
                'a-z' => $q->orderBy('title'),'featured' => $q->orderByDesc('is_featured')->orderBy('featured_order'),default => $q->latest('published_at')
            };
            $p = $q->paginate($d['per_page'] ?? 12);

            return ['items' => CourseResource::collection($p->items())->resolve($r), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]];
        });

        return $this->successResponse($payload['items'], 'Courses retrieved.', 200, $payload['meta']);
    }

    public function show(Request $r, string $slug): JsonResponse
    {
        $data = $this->cache->remember('detail:'.$slug, function () use ($slug, $r) {
            $c = $this->query()->with('seo.ogMedia')->where('slug', $slug)->first();
            if (! $c) {
                return null;
            }$related = $this->query()->whereKeyNot($c->id)->where(fn ($q) => $q->where('engineering_discipline_id', $c->engineering_discipline_id)->orWhere('course_level_id', $c->course_level_id))->limit(3)->get();

            return ['course' => (new CourseResource($c))->resolve($r), 'related' => CourseResource::collection($related)->resolve($r)];
        });
        abort_unless($data,404);

        return $this->successResponse($data);
    }
}
