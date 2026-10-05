<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class InventoryResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['book' => ['id' => $this->book->id, 'title' => $this->book->title, 'slug' => $this->book->slug, 'sku' => $this->book->sku], 'stock_quantity' => $this->stock_quantity, 'reserved_quantity' => $this->reserved_quantity, 'available_quantity' => $this->available_quantity, 'low_stock_threshold' => $this->low_stock_threshold, 'status' => $this->status, 'is_backorder_allowed' => $this->is_backorder_allowed, 'updated_at' => $this->updated_at];
    }
}
