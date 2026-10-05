<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ContributorAdminResource extends ContributorResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + ['biography' => $this->biography, 'email' => $this->email, 'phone' => $this->phone, 'media_id' => $this->media_id, 'discipline_ids' => $this->disciplines->pluck('id'), 'is_active' => $this->is_active, 'sort_order' => $this->sort_order, 'created_by' => $this->created_by, 'updated_by' => $this->updated_by, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at];
    }
}
