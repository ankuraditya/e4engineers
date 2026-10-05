<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\SeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Book;
use App\Services\BookCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BookSeoController extends Controller
{
    use ApiResponse;

    public function __construct(private BookCache $cache) {}

    public function show(Book $book): JsonResponse
    {
        Gate::authorize('seo.view');

        return $this->successResponse($book->seo ? new SeoResource($book->seo->load('ogMedia')) : null);
    }

    public function update(SeoRequest $r, Book $book): JsonResponse
    {
        Gate::authorize('seo.update');
        $seo = $book->seo()->updateOrCreate([], $r->validated());
        $this->cache->flush();

        return $this->successResponse(new SeoResource($seo->load('ogMedia')), 'SEO updated.');
    }
}
