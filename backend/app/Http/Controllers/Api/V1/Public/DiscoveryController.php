<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\Notice;
use App\Models\Video;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    use ApiResponse;

    private function published($q)
    {
        return $q->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function notices(Request $r): JsonResponse
    {
        $d = $r->validate(['search' => 'nullable|string|max:100', 'category' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:100', 'featured' => 'nullable|boolean', 'pinned' => 'nullable|boolean', 'status' => 'nullable|in:active,past', 'sort' => 'nullable|in:latest,oldest', 'per_page' => 'nullable|integer|min:1|max:50']);
        $q = $this->published(Notice::with(['category', 'discipline', 'featuredMedia', 'attachmentMedia', 'seo']))->when($d['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%$v%")->orWhere('short_description', 'like', "%$v%")))->when($d['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($x) => $x->where('slug', $v)))->when($d['discipline'] ?? null, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))->when(isset($d['featured']), fn ($q) => $q->where('is_featured', (bool) $d['featured']))->when(isset($d['pinned']), fn ($q) => $q->where('is_pinned', (bool) $d['pinned']));
        ($d['status'] ?? 'active') === 'past' ? $q->where('expires_at', '<', now()) : $q->where(fn ($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
        $p = $q->orderByDesc('is_pinned')->orderBy(($d['sort'] ?? 'latest') === 'oldest' ? 'notice_date' : 'notice_date', ($d['sort'] ?? 'latest') === 'oldest' ? 'asc' : 'desc')->paginate($d['per_page'] ?? 12);

        return $this->successResponse($p->items(), 'Notices retrieved.', 200, ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function notice(string $slug): JsonResponse
    {
        return $this->successResponse($this->published(Notice::with(['category', 'discipline', 'featuredMedia', 'attachmentMedia', 'seo.ogMedia']))->where('slug', $slug)->firstOrFail());
    }

    public function gallery(Request $r): JsonResponse
    {
        $p = $this->published(GalleryAlbum::with(['cover', 'images.media', 'discipline'])->withCount('images'))->when($r->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$r->input('search').'%'))->when($r->boolean('featured'), fn ($q) => $q->where('is_featured', true))->orderBy('sort_order')->paginate(min((int) $r->input('per_page', 12), 50));

        return $this->successResponse($p->items(), 'Gallery retrieved.', 200, ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function album(string $slug): JsonResponse
    {
        return $this->successResponse($this->published(GalleryAlbum::with(['cover', 'images.media', 'discipline', 'seo.ogMedia']))->where('slug', $slug)->firstOrFail());
    }

    public function videos(Request $r): JsonResponse
    {
        $p = $this->published(Video::with(['thumbnail', 'discipline', 'category']))->when($r->filled('search'), fn ($q) => $q->where(fn ($x) => $x->where('title', 'like', '%'.$r->input('search').'%')->orWhere('short_description', 'like', '%'.$r->input('search').'%')))->when($r->filled('discipline'), fn ($q) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $r->input('discipline'))))->orderByDesc('is_featured')->latest('published_at')->paginate(min((int) $r->input('per_page', 12), 50));
        $items = collect($p->items())->each->append('embed_url');

        return $this->successResponse($items, 'Videos retrieved.', 200, ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function video(string $slug): JsonResponse
    {
        $v = $this->published(Video::with(['thumbnail', 'discipline', 'category', 'seo.ogMedia']))->where('slug',$slug)->firstOrFail();

        return $this->successResponse($v->append('embed_url'));
    }
}
