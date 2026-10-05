<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\SeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Article;
use App\Services\ArticleCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ArticleSeoController extends Controller
{
    use ApiResponse;

    public function __construct(private ArticleCache $cache) {}

    public function show(Article $article): JsonResponse
    {
        Gate::authorize('seo.view');

        return $this->successResponse($article->seo ? new SeoResource($article->seo->load('ogMedia')) : null);
    }

    public function update(SeoRequest $r, Article $article): JsonResponse
    {
        Gate::authorize('seo.update');
        $seo = $article->seo()->updateOrCreate([], $r->validated());
        $this->cache->flush();

        return $this->successResponse(new SeoResource($seo->load('ogMedia')), 'SEO updated.');
    }
}
