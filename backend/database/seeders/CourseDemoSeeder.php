<?php

namespace Database\Seeders;

use App\Models\Contributor;
use App\Models\Course;
use App\Models\CourseLevel;
use App\Models\EngineeringDiscipline;
use Illuminate\Database\Seeder;

class CourseDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }$rows = [['Electrical Engineering', 'Beginner', 'Fundamentals of Electrical and Electronics Engineering', 'Build core concepts in circuits, machines, measurements and electronic devices.'], ['Mechanical Engineering', 'Intermediate', 'Thermodynamics for Engineers', 'Develop practical understanding of energy systems and thermodynamic cycles.'], ['Civil Engineering', 'Beginner', 'Structural Analysis Basics', 'Understand loads, reactions and the behaviour of structural systems.'], ['Computer Science Engineering', 'Beginner', 'Introduction to Computer Systems', 'Connect processor architecture, memory and practical system design.']];
        foreach ($rows as $i => [$dn,$ln,$title,$short]) {
            $d = EngineeringDiscipline::where('name', $dn)->first();
            $level = CourseLevel::where('name', $ln)->first();
            if (! $d) {
                continue;
            }$c = Course::updateOrCreate(['slug' => str($title)->slug()], ['title' => $title, 'short_description' => $short, 'description' => '<p>'.$short.'</p><p>This development course demonstrates the production course-management workflow.</p>', 'engineering_discipline_id' => $d->id, 'course_level_id' => $level?->id, 'mode' => 'self-paced', 'duration_value' => 6, 'duration_unit' => 'weeks', 'eligibility' => 'Suitable for engineering students, graduates and professionals building foundational knowledge.', 'currency' => 'INR', 'enrollment_type' => 'enquiry', 'status' => 'published', 'is_featured' => true, 'featured_order' => $i + 1, 'published_at' => now()->subDays($i)]);
            if (! $c->outcomes()->exists()) {
                foreach (['Understand core engineering principles', 'Apply concepts to practical systems', 'Interpret technical information'] as $j => $o) {
                    $c->outcomes()->create(['outcome' => $o, 'sort_order' => $j]);
                }
            }if (! $c->modules()->exists()) {
                foreach (['Introduction and engineering context', 'Core concepts', 'Practical applications'] as $j => $m) {
                    $module = $c->modules()->create(['title' => 'Module '.($j + 1).' — '.$m, 'sort_order' => $j]);
                    $module->lessons()->create(['title' => $m, 'lesson_type' => 'lecture', 'duration_minutes' => 45, 'sort_order' => 0]);
                }
            }if (! $c->faqs()->exists()) {
                $c->faqs()->create(['question' => 'Who should take this course?', 'answer' => '<p>Students and professionals seeking structured engineering knowledge.</p>', 'sort_order' => 0]);
            }$person = Contributor::whereHas('disciplines', fn ($q) => $q->whereKey($d->id))->first();
            if ($person) {
                $c->contributors()->syncWithoutDetaching([$person->id => ['role' => 'instructor', 'is_primary' => true, 'sort_order' => 0]]);
            }
        }
    }
}
