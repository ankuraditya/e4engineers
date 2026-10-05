<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Courses\ManageCourse;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Courses\StoreCourseRequest;
use App\Http\Requests\Api\V1\Courses\UpdateCourseRequest;
use App\Http\Resources\Api\V1\CourseAdminResource;
use App\Models\Course;
use App\Services\CourseCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(private ManageCourse $a, private CourseCache $cache) {}

    public function index(Request $r): JsonResponse
    {
        Gate::authorize('courses.view');
        $p = Course::query()->when($r->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))->when($r->status, fn ($q, $v) => $q->where('status', $v))->when($r->discipline, fn ($q, $v) => $q->where('engineering_discipline_id', $v))->when($r->level, fn ($q, $v) => $q->where('course_level_id', $v))->when($r->mode, fn ($q, $v) => $q->where('mode', $v))->when($r->filled('featured'), fn ($q) => $q->where('is_featured', $r->boolean('featured')))->with($this->a->relations())->latest()->paginate(min((int) $r->input('per_page', 20), 100));

        return $this->successResponse(CourseAdminResource::collection($p), meta: ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'per_page' => $p->perPage(), 'total' => $p->total()]);
    }

    public function store(StoreCourseRequest $r): JsonResponse
    {
        Gate::authorize('courses.create');

        return $this->successResponse(new CourseAdminResource($this->a->create($r->validated(), $r->user()->id)), 'Course created.', 201);
    }

    public function show(Course $course): JsonResponse
    {
        Gate::authorize('courses.view');

        return $this->successResponse(new CourseAdminResource($course->load($this->a->relations())));
    }

    public function update(UpdateCourseRequest $r, Course $course): JsonResponse
    {
        Gate::authorize('courses.update');

        return $this->successResponse(new CourseAdminResource($this->a->update($course, $r->validated(), $r->user()->id)), 'Course updated.');
    }

    public function status(Request $r, Course $course): JsonResponse
    {
        Gate::authorize('courses.publish');
        $d = $r->validate(['status' => ['required', Rule::enum(CourseStatus::class)], 'published_at' => 'nullable|date']);

        return $this->successResponse(new CourseAdminResource($this->a->update($course, $d, $r->user()->id)), 'Course status updated.');
    }

    public function featured(Request $r, Course $course): JsonResponse
    {
        Gate::authorize('courses.feature');
        $d = $r->validate(['is_featured' => 'required|boolean', 'featured_order' => 'nullable|integer|min:0']);

        return $this->successResponse(new CourseAdminResource($this->a->update($course, $d, $r->user()->id)), 'Course featured state updated.');
    }

    public function destroy(Course $course): JsonResponse
    {
        Gate::authorize('courses.delete');
        $course->delete();
        $this->cache->flush();

        return $this->successResponse(null,'Course deleted.');
    }
}
