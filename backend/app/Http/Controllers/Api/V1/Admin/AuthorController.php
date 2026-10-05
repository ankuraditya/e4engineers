<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthorResource;
use App\Models\Author;
use App\Services\BookCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthorController extends Controller
{
    use ApiResponse;

    public function __construct(private BookCache $cache) {}

    private function rules(Request $r, ?Author $a = null): array
    {
        return ['name' => 'required|string|max:255', 'slug' => ['nullable', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('authors')->ignore($a?->id)], 'biography' => 'nullable|string|max:10000', 'photo_media_id' => 'nullable|exists:media,id', 'website_url' => 'nullable|url:http,https|max:2048', 'is_active' => 'sometimes|boolean', 'sort_order' => 'sometimes|integer|min:0'];
    }

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('authors.view');

        return $this->successResponse(AuthorResource::collection(Author::with('photo')->paginate(min((int) $r->input('per_page', 20), 100))));
    }

    public function store(Request $r): JsonResponse
    {
        Gate::authorize('authors.create');
        $d = $r->validate($this->rules($r));
        $d['slug'] ??= Str::slug($d['name']);
        $a = Author::create($d);
        $this->cache->flush();

        return $this->successResponse(new AuthorResource($a->load('photo')), 'Author created.', 201);
    }

    public function show(Author $author): JsonResponse
    {
        Gate::authorize('authors.view');

        return $this->successResponse(new AuthorResource($author->load('photo')));
    }

    public function update(Request $r, Author $author): JsonResponse
    {
        Gate::authorize('authors.update');
        $d = $r->validate($this->rules($r, $author));
        $author->update($d);
        $this->cache->flush();

        return $this->successResponse(new AuthorResource($author->refresh()->load('photo')));
    }

    public function destroy(Author $author): JsonResponse
    {
        Gate::authorize('authors.delete');
        abort_if($author->books()->exists(), 422, 'Author is assigned to books.');
        $author->delete();
        $this->cache->flush();

        return $this->successResponse(null, 'Author deleted.');
    }
}
