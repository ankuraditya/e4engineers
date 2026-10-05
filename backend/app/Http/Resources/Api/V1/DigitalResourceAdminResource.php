<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class DigitalResourceAdminResource extends DigitalResourceResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), ['description' => $this->description, 'resource_type_id' => $this->resource_type_id, 'engineering_discipline_id' => $this->engineering_discipline_id, 'status' => $this->status->value, 'thumbnail_media_id' => $this->thumbnail_media_id, 'preview_media_id' => $this->preview_media_id, 'file_media_id' => $this->file_media_id, 'featured_order' => $this->featured_order, 'sort_order' => $this->sort_order, 'created_by' => $this->created_by, 'updated_by' => $this->updated_by]);
    }
}
