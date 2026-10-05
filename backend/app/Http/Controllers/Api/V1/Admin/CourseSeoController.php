<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\SeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Course;
use App\Services\CourseCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CourseSeoController extends Controller
{
    use ApiResponse;

    public function __construct(private CourseCache $cache) {}

    public function show(Course $course): JsonResponse
    {
        Gate::authorize('seo.view');

        return $this->successResponse($course->seo ? new SeoResource($course->seo->load('ogMedia')) : null);
    }

    public function update(SeoRequest $r, Course $course): JsonResponse
    {
        Gate::authorize('seo.update');
        $seo = $course->seo()->updateOrCreate([], $r->validated());
        $this->cache->flush();

        return $this->successResponse(new SeoResource($seo->load('ogMedia')), 'SEO updated.');
    }
}
