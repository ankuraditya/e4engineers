<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Book;
use App\Models\BookInventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

final class InventoryService
{
    public function __construct(private BookCache $cache) {}

    public function initialize(Book $book): BookInventory
    {
        return $book->inventory()->firstOrCreate([], ['stock_quantity' => 0, 'reserved_quantity' => 0, 'low_stock_threshold' => config('e4engineers.inventory.low_stock_threshold', 5)]);
    }

    public function increase(Book $book, int $quantity, InventoryMovementType $type, string $reason, ?int $actor = null, ?string $notes = null, ?string $referenceType = null, ?string $referenceId = null): BookInventory
    {
        return $this->change($book, $quantity, $type, $reason, $actor, $notes, $referenceType, $referenceId);
    }

    public function decrease(Book $book, int $quantity, InventoryMovementType $type, string $reason, ?int $actor = null, ?string $notes = null, ?string $referenceType = null, ?string $referenceId = null): BookInventory
    {
        return $this->change($book, -$quantity, $type, $reason, $actor, $notes, $referenceType, $referenceId);
    }

    public function adjust(Book $book, int $target, string $reason, ?int $actor = null, ?string $notes = null): BookInventory
    {
        return DB::transaction(function () use ($book, $target, $reason, $actor, $notes) {
            $inventory = $this->locked($book);
            $delta = $target - $inventory->stock_quantity;

            return $this->apply($inventory, $delta, InventoryMovementType::Adjustment, $reason, $actor, $notes);
        });
    }

    public function updateThreshold(Book $book, int $threshold): BookInventory
    {
        return DB::transaction(function () use ($book, $threshold) {
            $inventory = $this->locked($book);
            $inventory->update(['low_stock_threshold' => $threshold]);
            $this->cache->flush();

            return $inventory->refresh();
        });
    }

    public function ensureAvailable(Book $book, int $quantity): void
    {
        $inventory = $this->initialize($book);
        if ($quantity < 1 || $inventory->available_quantity < $quantity) {
            throw new InsufficientStockException;
        }
    }

    public function reserve(Book $book, int $quantity, ?string $referenceType = null, ?string $referenceId = null): BookInventory
    {
        return DB::transaction(function () use ($book, $quantity, $referenceType, $referenceId) {
            $inventory = $this->locked($book);
            if ($quantity < 1 || $inventory->available_quantity < $quantity) {
                throw new InsufficientStockException;
            }$before = $inventory->stock_quantity;
            $inventory->increment('reserved_quantity', $quantity);
            InventoryMovement::create(['book_id' => $book->id, 'type' => InventoryMovementType::Reservation, 'quantity' => 0, 'quantity_before' => $before, 'quantity_after' => $before, 'reference_type' => $referenceType, 'reference_id' => $referenceId, 'reason' => 'Stock reservation']);
            $this->cache->flush();

            return $inventory->refresh();
        });
    }

    public function finalizeReservation(Book $book, int $quantity, string $referenceId): BookInventory
    {
        return DB::transaction(function () use ($book, $quantity, $referenceId) {
            $inventory = $this->locked($book);
            if ($quantity < 1 || $inventory->reserved_quantity < $quantity || $inventory->stock_quantity < $quantity) {
                throw new InsufficientStockException;
            }
            $before = $inventory->stock_quantity;
            $inventory->update(['stock_quantity' => $before - $quantity, 'reserved_quantity' => $inventory->reserved_quantity - $quantity]);
            InventoryMovement::create(['book_id' => $book->id, 'type' => InventoryMovementType::Sale, 'quantity' => -$quantity, 'quantity_before' => $before, 'quantity_after' => $before - $quantity, 'reference_type' => 'order', 'reference_id' => $referenceId, 'reason' => 'Online payment captured']);
            $this->cache->flush();

            return $inventory->refresh();
        });
    }

    public function releaseReservation(Book $book, int $quantity, string $referenceId): BookInventory
    {
        return DB::transaction(function () use ($book, $quantity, $referenceId) {
            $inventory = $this->locked($book);
            $release = min($quantity, $inventory->reserved_quantity);
            if ($release > 0) {
                $before = $inventory->stock_quantity;
                $inventory->decrement('reserved_quantity', $release);
                InventoryMovement::create(['book_id' => $book->id, 'type' => InventoryMovementType::ReservationRelease, 'quantity' => 0, 'quantity_before' => $before, 'quantity_after' => $before, 'reference_type' => 'order', 'reference_id' => $referenceId, 'reason' => 'Payment reservation released']);
            }
            $this->cache->flush();

            return $inventory->refresh();
        });
    }

    private function change(Book $book, int $delta, InventoryMovementType $type, string $reason, ?int $actor, ?string $notes, ?string $referenceType, ?string $referenceId): BookInventory
    {
        return DB::transaction(function () use ($book, $delta, $type, $reason, $actor, $notes, $referenceType, $referenceId) {
            return $this->apply($this->locked($book), $delta, $type, $reason, $actor, $notes, $referenceType, $referenceId);
        });
    }

    private function locked(Book $book): BookInventory
    {
        $this->initialize($book);

        return BookInventory::where('book_id', $book->id)->lockForUpdate()->firstOrFail();
    }

    private function apply(BookInventory $inventory, int $delta, InventoryMovementType $type, string $reason, ?int $actor, ?string $notes, ?string $referenceType = null, ?string $referenceId = null): BookInventory
    {
        $before = $inventory->stock_quantity;
        $after = $before + $delta;
        if ($after < 0 || $after < $inventory->reserved_quantity) {
            throw new InsufficientStockException;
        }
        $inventory->update(['stock_quantity' => $after]);
        if ($delta !== 0) {
            InventoryMovement::create(['book_id' => $inventory->book_id, 'type' => $type, 'quantity' => $delta, 'quantity_before' => $before, 'quantity_after' => $after, 'reference_type' => $referenceType, 'reference_id' => $referenceId, 'reason' => $reason, 'notes' => $notes, 'created_by' => $actor]);
        }
        $this->cache->flush();

        return $inventory->refresh();
    }
}
