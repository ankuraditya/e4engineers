<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EngineeringDisciplineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'short_name' => $this->short_name, 'description' => $this->description, 'icon' => $this->icon, 'image' => $this->image, 'sort_order' => $this->sort_order, 'is_active' => $this->is_active];
    }
}
