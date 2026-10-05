<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class CourseAdminResource extends CourseResource
{
    public function toArray(Request $r): array
    {
        return array_merge(parent::toArray($r), ['description' => $this->description, 'eligibility' => $this->eligibility, 'engineering_discipline_id' => $this->engineering_discipline_id, 'course_level_id' => $this->course_level_id, 'featured_media_id' => $this->featured_media_id, 'status' => $this->status->value, 'featured_order' => $this->featured_order, 'sort_order' => $this->sort_order, 'created_by' => $this->created_by, 'updated_by' => $this->updated_by]);
    }
}
