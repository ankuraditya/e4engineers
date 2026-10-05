<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PublicationAdminResource extends PublicationResource
{
    public function toArray(Request $r): array
    {
        return array_merge(parent::toArray($r), ['description' => $this->description, 'publication_type_id' => $this->publication_type_id, 'engineering_discipline_id' => $this->engineering_discipline_id, 'featured_media_id' => $this->featured_media_id, 'status' => $this->status->value, 'featured_order' => $this->featured_order, 'sort_order' => $this->sort_order, 'file_media_id' => $this->file_media_id, 'created_by' => $this->created_by, 'updated_by' => $this->updated_by]);
    }
}
