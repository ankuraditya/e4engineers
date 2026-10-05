<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use App\Services\BookCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use ApiResponse;

    public function __construct(private BookCache $cache) {}

    private function query()
    {
        return Book::query()->where('status', BookStatus::Published)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->with(['discipline', 'category', 'publisher', 'cover', 'authors', 'images.media', 'inventory']);
    }

    public function index(Request $r): JsonResponse
    {
        $d = $r->validate(['search' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:255', 'category' => 'nullable|string|max:255', 'author' => 'nullable|string|max:255', 'publisher' => 'nullable|string|max:255', 'min_price' => 'nullable|numeric|min:0', 'max_price' => 'nullable|numeric|min:0|gte:min_price', 'featured' => 'nullable|boolean', 'new_arrival' => 'nullable|boolean', 'availability' => 'nullable|in:in-stock,low-stock,out-of-stock', 'sort' => 'nullable|in:latest,a-z,price-low-high,price-high-low,featured', 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100']);
        $payload = $this->cache->remember('list:'.hash('sha256', json_encode($d)), function () use ($d, $r) {
            $q = $this->query()
                ->when($d['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%{$v}%")->orWhere('short_description', 'like', "%{$v}%")->orWhere('isbn', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%")))
                ->when($d['discipline'] ?? null, fn ($q, $v) => $q->whereHas('discipline', fn ($x) => $x->where('slug', $v)))->when($d['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($x) => $x->where('slug', $v)))
                ->when($d['author'] ?? null, fn ($q, $v) => $q->whereHas('authors', fn ($x) => $x->where('slug', $v)))->when($d['publisher'] ?? null, fn ($q, $v) => $q->whereHas('publisher', fn ($x) => $x->where('slug', $v)))
                ->when($d['min_price'] ?? null, fn ($q, $v) => $q->where('selling_price', '>=', $v))->when($d['max_price'] ?? null, fn ($q, $v) => $q->where('selling_price', '<=', $v))->when(array_key_exists('featured', $d), fn ($q) => $q->where('is_featured', (bool) $d['featured']))->when(array_key_exists('new_arrival', $d), fn ($q) => $q->where('is_new_arrival', (bool) $d['new_arrival']));
            $q->when($d['availability'] ?? null, function ($query, $value) {
                return $query->whereHas('inventory', fn ($inventory) => match ($value) {
                    'out-of-stock' => $inventory->whereRaw('(stock_quantity - reserved_quantity) <= 0'),
                    'low-stock' => $inventory->whereRaw('(stock_quantity - reserved_quantity) > 0')->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold'),
                    default => $inventory->whereRaw('(stock_quantity - reserved_quantity) > 0'),
                });
            });
            match ($d['sort'] ?? 'latest') {
                'a-z' => $q->orderBy('title'),'price-low-high' => $q->orderBy('selling_price'),'price-high-low' => $q->orderByDesc('selling_price'),'featured' => $q->orderByDesc('is_featured')->orderBy('featured_order'),default => $q->latest('published_at')
            };
            $p = $q->paginate($d['per_page'] ?? 12);

            return ['items' => BookResource::collection($p->items())->resolve($r), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]];
        });

        return $this->successResponse($payload['items'], 'Books retrieved.', 200, $payload['meta']);
    }

    public function show(Request $r, string $slug): JsonResponse
    {
        $data = $this->cache->remember('detail:'.$slug, function () use ($slug, $r) {
            $book = $this->query()->with('seo.ogMedia')->where('slug', $slug)->first();
            if (! $book) {
                return null;
            } $related = $this->query()->whereKeyNot($book->id)->where(fn ($q) => $q->where('engineering_discipline_id', $book->engineering_discipline_id)->orWhere('category_id', $book->category_id)->orWhereHas('authors', fn ($x) => $x->whereIn('authors.id', $book->authors->pluck('id'))))->limit(4)->get();

            return ['book' => (new BookResource($book))->resolve($r), 'related' => BookResource::collection($related)->resolve($r)];
        });
        abort_unless($data, 404);

        return $this->successResponse($data);
    }
}
