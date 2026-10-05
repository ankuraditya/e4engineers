<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\EngineeringDiscipline;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;

class LegacyWordPressCourseSeeder extends Seeder
{
    public function run(): void
    {
        $source = file_get_contents(__DIR__.'/legacy-wordpress-course.json');
        $courseData = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $source), true, 512, JSON_THROW_ON_ERROR);
        $course = Course::withTrashed()->firstOrNew(['slug' => $courseData['slug']]);
        $wasDemo = str_contains($course->description ?? '', 'This development course demonstrates the production course-management workflow.');
        if ($course->exists && ! $wasDemo) {
            return;
        }

        $course->fill([
            'title' => trim(html_entity_decode(strip_tags($courseData['title']['rendered']), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'short_description' => trim(html_entity_decode(strip_tags($courseData['excerpt']['rendered']), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'description' => app(HtmlSanitizer::class)->clean($courseData['content']['rendered']),
            'engineering_discipline_id' => EngineeringDiscipline::where('slug', 'electrical-engineering')->firstOrFail()->id,
            'course_level_id' => CourseLevel::where('name', 'Beginner')->first()?->id,
            'mode' => 'online',
            'duration_value' => null,
            'duration_unit' => null,
            'eligibility' => null,
            'featured_media_id' => null,
            'fee' => null,
            'currency' => 'INR',
            'enrollment_type' => 'enquiry',
            'status' => 'published',
            'is_featured' => true,
            'featured_order' => 1,
            'published_at' => $courseData['date'],
            'deleted_at' => null,
        ]);
        $course->save();

        if ($wasDemo) {
            foreach ($course->modules as $module) {
                $module->lessons()->delete();
                $module->delete();
            }
            $course->outcomes()->delete();
            $course->faqs()->delete();
            $course->contributors()->detach();
        }
    }
}
