<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ArticleAdminResource extends ArticleResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), ['content' => $this->content, 'content_format' => $this->content_format, 'engineering_discipline_id' => $this->engineering_discipline_id, 'featured_media_id' => $this->featured_media_id, 'status' => $this->status->value, 'author_name' => $this->author_name, 'featured_order' => $this->featured_order, 'sort_order' => $this->sort_order, 'created_by' => $this->created_by, 'updated_by' => $this->updated_by, 'deleted_at' => $this->deleted_at]);
    }
}
