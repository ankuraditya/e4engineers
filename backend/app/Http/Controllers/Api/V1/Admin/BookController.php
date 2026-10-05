<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Books\ManageBook;
use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Books\StoreBookRequest;
use App\Http\Requests\Api\V1\Books\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookAdminResource;
use App\Models\Book;
use App\Services\BookCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    use ApiResponse;

    public function __construct(private ManageBook $action, private BookCache $cache) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('books.view');
        $p = Book::query()->when($r->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))->when($r->status, fn ($q, $v) => $q->where('status', $v))->when($r->discipline, fn ($q, $v) => $q->whereHas('disciplines', fn ($x) => $x->whereKey($v)))->when($r->category, fn ($q, $v) => $q->where('category_id', $v))->when($r->publisher, fn ($q, $v) => $q->where('publisher_id', $v))->when($r->author, fn ($q, $v) => $q->whereHas('authors', fn ($x) => $x->where('authors.id', $v)))->when($r->filled('featured'), fn ($q) => $q->where('is_featured', $r->boolean('featured')))->when($r->filled('new_arrival'), fn ($q) => $q->where('is_new_arrival', $r->boolean('new_arrival')))->with($this->action->relations())->latest()->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(BookAdminResource::collection($p), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]);
    }

    public function store(StoreBookRequest $r): JsonResponse
    {
        Gate::authorize('books.create');

        return $this->successResponse(new BookAdminResource($this->action->create($r->validated(), $r->user()->id)), 'Book created.', 201);
    }

    public function show(Book $book): JsonResponse
    {
        Gate::authorize('books.view');

        return $this->successResponse(new BookAdminResource($book->load($this->action->relations())));
    }

    public function update(UpdateBookRequest $r, Book $book): JsonResponse
    {
        Gate::authorize('books.update');

        return $this->successResponse(new BookAdminResource($this->action->update($book, $r->validated(), $r->user()->id)), 'Book updated.');
    }

    public function status(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('books.publish');
        $d = $r->validate(['status' => ['required', Rule::enum(BookStatus::class)], 'published_at' => 'nullable|date']);

        return $this->successResponse(new BookAdminResource($this->action->update($book, $d, $r->user()->id)), 'Status updated.');
    }

    public function featured(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('books.feature');
        $d = $r->validate(['is_featured' => 'required|boolean', 'featured_order' => 'nullable|integer|min:0']);

        return $this->successResponse(new BookAdminResource($this->action->update($book, $d, $r->user()->id)), 'Featured state updated.');
    }

    public function newArrival(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('books.update');
        $d = $r->validate(['is_new_arrival' => 'required|boolean']);

        return $this->successResponse(new BookAdminResource($this->action->update($book, $d, $r->user()->id)), 'New-arrival state updated.');
    }

    public function gallery(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('books.images.manage');
        $d = $r->validate(['gallery' => 'required|array', 'gallery.*.media_id' => 'required|integer|distinct|exists:media,id', 'gallery.*.is_primary' => 'sometimes|boolean']);

        return $this->successResponse(new BookAdminResource($this->action->update($book, $d, $r->user()->id)), 'Gallery updated.');
    }

    public function destroy(Book $book): JsonResponse
    {
        Gate::authorize('books.delete');
        $book->delete();
        $this->cache->flush();

        return $this->successResponse(null, 'Book deleted.');
    }
}
