<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InventoryMovementResource;
use App\Http\Resources\Api\V1\InventoryResource;
use App\Models\Book;
use App\Models\BookInventory;
use App\Services\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(private InventoryService $inventory) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('inventory.view');
        $q = BookInventory::with('book')->when($r->search, fn ($q, $v) => $q->whereHas('book', fn ($b) => $b->where('title', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%")))->when($r->discipline, fn ($q, $v) => $q->whereHas('book', fn ($b) => $b->where('engineering_discipline_id', $v)))->when($r->category, fn ($q, $v) => $q->whereHas('book', fn ($b) => $b->where('category_id', $v)));
        if ($r->status === 'OUT_OF_STOCK' || $r->boolean('out_of_stock')) {
            $q->whereRaw('(stock_quantity-reserved_quantity)<=0');
        }if ($r->status === 'LOW_STOCK' || $r->boolean('low_stock')) {
            $q->whereRaw('(stock_quantity-reserved_quantity)>0')->whereRaw('(stock_quantity-reserved_quantity)<=low_stock_threshold');
        }if ($r->status === 'IN_STOCK') {
            $q->whereRaw('(stock_quantity-reserved_quantity)>low_stock_threshold');
        }$p = $q->latest('updated_at')->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(InventoryResource::collection($p), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]);
    }

    public function show(Book $book): JsonResponse
    {
        Gate::authorize('inventory.view');
        $i = $this->inventory->initialize($book)->load('book');
        $d = (new InventoryResource($i))->resolve();
        $d['recent_movements'] = InventoryMovementResource::collection($book->inventoryMovements()->latest('created_at')->limit(20)->get())->resolve();

        return $this->successResponse($d);
    }

    public function increase(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('inventory.increase');
        $d = $this->movementData($r);
        $i = $this->inventory->increase($book, $d['quantity'], InventoryMovementType::ManualIncrease, $d['reason'], $r->user()->id, $d['notes'] ?? null);

        return $this->successResponse(new InventoryResource($i->load('book')), 'Stock increased.');
    }

    public function decrease(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('inventory.decrease');
        $d = $this->movementData($r);
        $i = $this->inventory->decrease($book, $d['quantity'], InventoryMovementType::ManualDecrease, $d['reason'], $r->user()->id, $d['notes'] ?? null);

        return $this->successResponse(new InventoryResource($i->load('book')), 'Stock decreased.');
    }

    public function adjust(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('inventory.adjust');
        $d = $r->validate(['target_quantity' => 'required|integer|min:0', 'reason' => 'required|string|max:500', 'notes' => 'nullable|string|max:5000']);
        $i = $this->inventory->adjust($book, $d['target_quantity'], $d['reason'], $r->user()->id, $d['notes'] ?? null);

        return $this->successResponse(new InventoryResource($i->load('book')), 'Stock adjusted.');
    }

    public function threshold(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('inventory.threshold.update');
        $d = $r->validate(['low_stock_threshold' => 'required|integer|min:0']);
        $i = $this->inventory->updateThreshold($book, $d['low_stock_threshold']);

        return $this->successResponse(new InventoryResource($i->load('book')), 'Threshold updated.');
    }

    public function movements(Request $r, Book $book): JsonResponse
    {
        Gate::authorize('inventory.movements.view');
        $d = $r->validate(['type' => ['nullable', Rule::enum(InventoryMovementType::class)], 'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from', 'sort' => 'nullable|in:latest,oldest', 'per_page' => 'nullable|integer|min:1|max:100']);
        $q = $book->inventoryMovements()->when($d['type'] ?? null, fn ($q, $v) => $q->where('type', $v))->when($d['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($d['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
        ($d['sort'] ?? 'latest') === 'oldest' ? $q->oldest('created_at') : $q->latest('created_at');
        $p = $q->paginate($d['per_page'] ?? 20);

        return $this->successResponse(InventoryMovementResource::collection($p), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]);
    }

    private function movementData(Request $r): array
    {
        return $r->validate(['quantity' => 'required|integer|min:1', 'reason' => 'required|string|max:500', 'notes' => 'nullable|string|max:5000']);
    }
}
