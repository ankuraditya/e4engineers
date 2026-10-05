<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class BookAdminResource extends BookResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'description' => $this->description,
            'engineering_discipline_id' => $this->engineering_discipline_id,
            'discipline_ids' => $this->disciplines->pluck('id')->values(),
            'category_id' => $this->category_id,
            'publisher_id' => $this->publisher_id,
            'cover_media_id' => $this->cover_media_id,
            'gallery' => $this->images->map(fn ($image) => ['media_id' => $image->media_id, 'url' => $image->media?->url, 'is_primary' => $image->is_primary])->values(),
            'inventory' => $this->inventory ? ['stock_quantity' => $this->inventory->stock_quantity, 'reserved_quantity' => $this->inventory->reserved_quantity, 'available_quantity' => $this->inventory->available_quantity, 'low_stock_threshold' => $this->inventory->low_stock_threshold, 'status' => $this->inventory->status] : null,
            'status' => $this->status->value,
            'weight_grams' => $this->weight_grams,
            'length_cm' => $this->length_cm,
            'width_cm' => $this->width_cm,
            'height_cm' => $this->height_cm,
            'shipping_enabled' => $this->shipping_enabled,
            'featured_order' => $this->featured_order,
            'sort_order' => $this->sort_order,
            'published_at' => $this->published_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ]);
    }
}
