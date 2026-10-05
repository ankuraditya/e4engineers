<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class InventoryMovementResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'type' => $this->type->value, 'quantity' => $this->quantity, 'quantity_before' => $this->quantity_before, 'quantity_after' => $this->quantity_after, 'reference_type' => $this->reference_type, 'reference_id' => $this->reference_id, 'reason' => $this->reason, 'notes' => $this->notes, 'created_by' => $this->created_by, 'created_at' => $this->created_at];
    }
}
