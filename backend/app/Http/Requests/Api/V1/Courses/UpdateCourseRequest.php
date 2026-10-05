<?php

namespace App\Http\Requests\Api\V1\Courses;

class UpdateCourseRequest extends StoreCourseRequest
{
    public function rules(): array
    {
        return $this->rulesFor($this->route('course')?->id, true);
    }
}
