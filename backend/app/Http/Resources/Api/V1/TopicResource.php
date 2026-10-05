<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'engineering_discipline_id' => $this->engineering_discipline_id, 'parent_id' => $this->parent_id, 'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description, 'sort_order' => $this->sort_order, 'is_active' => $this->is_active];
    }
}
