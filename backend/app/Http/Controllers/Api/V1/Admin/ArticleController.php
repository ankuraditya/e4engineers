<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Articles\ManageArticle;
use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Articles\StoreArticleRequest;
use App\Http\Requests\Api\V1\Articles\UpdateArticleRequest;
use App\Http\Resources\Api\V1\ArticleAdminResource;
use App\Models\Article;
use App\Services\ArticleCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    use ApiResponse;

    public function __construct(private ManageArticle $action, private ArticleCache $cache) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('articles.view');
        $q = Article::query()->when($r->search, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")))->when($r->status, fn ($q, $v) => $q->where('status', $v))->when($r->discipline, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))->when($r->category, fn ($q, $v) => $q->where('category_id', $v))->when($r->topic, fn ($q, $v) => $q->where('topic_id', $v))->when($r->contributor, fn ($q, $v) => $q->whereHas('contributors', fn ($x) => $x->whereKey($v)))->when($r->filled('featured'), fn ($q) => $q->where('is_featured', $r->boolean('featured')))->when($r->from, fn ($q, $v) => $q->whereDate('published_at', '>=', $v))->when($r->to, fn ($q, $v) => $q->whereDate('published_at', '<=', $v))->with(['discipline', 'category', 'topic', 'tags', 'contributors.media', 'featuredMedia'])->latest()->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(ArticleAdminResource::collection($q), meta: ['current_page' => $q->currentPage(), 'last_page' => $q->lastPage(), 'per_page' => $q->perPage(), 'total' => $q->total()]);
    }

    public function store(StoreArticleRequest $r): JsonResponse
    {
        Gate::authorize('articles.create');

        return $this->successResponse(new ArticleAdminResource($this->action->create($r->validated(), $r->user()->id)), 'Article created.', 201);
    }

    public function show(Article $article): JsonResponse
    {
        Gate::authorize('articles.view');

        return $this->successResponse(new ArticleAdminResource($article->load(['discipline', 'category', 'topic', 'tags', 'contributors.media', 'featuredMedia', 'seo.ogMedia'])));
    }

    public function update(UpdateArticleRequest $r, Article $article): JsonResponse
    {
        Gate::authorize('articles.update');

        return $this->successResponse(new ArticleAdminResource($this->action->update($article, $r->validated(), $r->user()->id)), 'Article updated.');
    }

    public function status(Request $r, Article $article): JsonResponse
    {
        Gate::authorize('articles.publish');
        $data = $r->validate(['status' => ['required', Rule::enum(ArticleStatus::class)], 'published_at' => 'nullable|date']);

        return $this->successResponse(new ArticleAdminResource($this->action->update($article, $data, $r->user()->id)), 'Article status updated.');
    }

    public function featured(Request $r, Article $article): JsonResponse
    {
        Gate::authorize('articles.feature');
        $data = $r->validate(['is_featured' => 'required|boolean', 'featured_order' => 'nullable|integer|min:0']);

        return $this->successResponse(new ArticleAdminResource($this->action->update($article, $data, $r->user()->id)), 'Article featured state updated.');
    }

    public function destroy(Article $article): JsonResponse
    {
        Gate::authorize('articles.delete');
        $article->delete();
        $this->cache->flush();

        return $this->successResponse(null,'Article deleted.');
    }
}
