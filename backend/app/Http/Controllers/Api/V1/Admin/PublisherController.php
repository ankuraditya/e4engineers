<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublisherResource;
use App\Models\Publisher;
use App\Services\BookCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublisherController extends Controller
{
    use ApiResponse;

    public function __construct(private BookCache $cache) {}

    private function rules(Request $r, ?Publisher $p = null): array
    {
        return ['name' => 'required|string|max:255', 'slug' => ['nullable', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('publishers')->ignore($p?->id)], 'description' => 'nullable|string|max:10000', 'logo_media_id' => 'nullable|exists:media,id', 'website_url' => 'nullable|url:http,https|max:2048', 'is_active' => 'sometimes|boolean', 'sort_order' => 'sometimes|integer|min:0'];
    }

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('publishers.view');

        return $this->successResponse(PublisherResource::collection(Publisher::with('logo')->paginate(min((int) $r->input('per_page', 20), 100))));
    }

    public function store(Request $r): JsonResponse
    {
        Gate::authorize('publishers.create');
        $d = $r->validate($this->rules($r));
        $d['slug'] ??= Str::slug($d['name']);
        $p = Publisher::create($d);
        $this->cache->flush();

        return $this->successResponse(new PublisherResource($p->load('logo')), 'Publisher created.', 201);
    }

    public function show(Publisher $publisher): JsonResponse
    {
        Gate::authorize('publishers.view');

        return $this->successResponse(new PublisherResource($publisher->load('logo')));
    }

    public function update(Request $r, Publisher $publisher): JsonResponse
    {
        Gate::authorize('publishers.update');
        $d = $r->validate($this->rules($r, $publisher));
        $publisher->update($d);
        $this->cache->flush();

        return $this->successResponse(new PublisherResource($publisher->refresh()->load('logo')));
    }

    public function destroy(Publisher $publisher): JsonResponse
    {
        Gate::authorize('publishers.delete');
        abort_if($publisher->books()->exists(), 422, 'Publisher is assigned to books.');
        $publisher->delete();
        $this->cache->flush();

        return $this->successResponse(null, 'Publisher deleted.');
    }
}
