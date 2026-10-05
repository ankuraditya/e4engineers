<?php

namespace App\Http\Requests\Api\V1\Courses;

use App\Enums\CourseMode;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentType;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends ApiRequest
{
    public function rules(): array
    {
        return $this->rulesFor();
    }

    protected function rulesFor(?int $id = null, bool $partial = false): array
    {
        $r = $partial ? 'sometimes' : 'required';

        return ['title' => [$r, 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('courses')->ignore($id)], 'short_description' => ['nullable', 'string', 'max:2000'], 'description' => [$r, 'string', 'max:200000'], 'engineering_discipline_id' => [$r, 'integer', 'exists:engineering_disciplines,id'], 'course_level_id' => ['nullable', 'integer', 'exists:course_levels,id'], 'mode' => [$r, Rule::enum(CourseMode::class)], 'duration_value' => ['nullable', 'integer', 'min:1'], 'duration_unit' => ['nullable', Rule::in(['hours', 'days', 'weeks', 'months'])], 'eligibility' => ['nullable', 'string', 'max:5000'], 'featured_media_id' => ['nullable', 'integer', 'exists:media,id'], 'fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'], 'currency' => ['sometimes', 'string', 'size:3'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'enrollment_type' => ['sometimes', Rule::enum(EnrollmentType::class)], 'enrollment_url' => ['nullable', 'url:http,https', 'max:500', 'required_if:enrollment_type,external-url'], 'status' => ['sometimes', Rule::enum(CourseStatus::class)], 'is_featured' => ['sometimes', 'boolean'], 'featured_order' => ['nullable', 'integer', 'min:0'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'outcomes' => ['sometimes', 'array'], 'outcomes.*.outcome' => ['required', 'string', 'max:1000'], 'modules' => ['sometimes', 'array'], 'modules.*.title' => ['required', 'string', 'max:255'], 'modules.*.description' => ['nullable', 'string', 'max:5000'], 'modules.*.is_active' => ['sometimes', 'boolean'], 'modules.*.lessons' => ['sometimes', 'array'], 'modules.*.lessons.*.title' => ['required', 'string', 'max:255'], 'modules.*.lessons.*.description' => ['nullable', 'string', 'max:5000'], 'modules.*.lessons.*.lesson_type' => ['nullable', Rule::in(['lecture', 'reading', 'practical', 'assessment', 'other'])], 'modules.*.lessons.*.duration_minutes' => ['nullable', 'integer', 'min:1'], 'contributors' => ['sometimes', 'array'], 'contributors.*.id' => ['required', 'integer', 'distinct', 'exists:contributors,id'], 'contributors.*.role' => ['sometimes', Rule::in(['instructor', 'faculty', 'subject-matter-expert', 'guest-faculty'])], 'contributors.*.is_primary' => ['sometimes', 'boolean'], 'faqs' => ['sometimes', 'array'], 'faqs.*.question' => ['required', 'string', 'max:500'], 'faqs.*.answer' => ['required', 'string', 'max:10000'], 'faqs.*.is_active' => ['sometimes', 'boolean']];
    }
}
