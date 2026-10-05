<?php

namespace Tests\Feature;

use App\Models\EngineeringDiscipline;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\CourseLevelsSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class, CourseLevelsSeeder::class]);
    }

    private function user(string $r): User
    {
        $u = User::factory()->create();
        $u->assignRole($r);

        return $u;
    }

    private function data(): array
    {
        return ['title' => 'Test Course', 'description' => '<p>Safe</p>', 'engineering_discipline_id' => EngineeringDiscipline::first()->id, 'mode' => 'self-paced', 'status' => 'published', 'outcomes' => [['outcome' => 'Learn safely']], 'modules' => [['title' => 'Module 1', 'lessons' => [['title' => 'Lesson 1', 'lesson_type' => 'lecture']]]], 'faqs' => [['question' => 'For whom?', 'answer' => '<p>Everyone</p>']]];
    }

    public function test_course_manager_creates_complete_course(): void
    {
        $d = $this->actingAs($this->user('course-manager'), 'web')->postJson('/api/v1/admin/courses', $this->data())->assertCreated()->assertJsonPath('data.slug', 'test-course')->json('data');
        $this->getJson('/api/v1/courses/test-course')->assertOk()->assertJsonCount(1, 'data.course.learning_outcomes')->assertJsonCount(1, 'data.course.curriculum')->assertJsonCount(1, 'data.course.faqs');
    }

    public function test_public_filters_hide_drafts(): void
    {
        $this->actingAs($this->user('course-manager'), 'web')->postJson('/api/v1/admin/courses', $this->data());
        $draft = $this->data();
        $draft['title'] = 'Draft Course';
        $draft['status'] = 'draft';
        $this->postJson('/api/v1/admin/courses', $draft);
        $this->getJson('/api/v1/courses?mode=self-paced')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_permissions_are_conservative(): void
    {
        $this->actingAs($this->user('content-manager'), 'web')->getJson('/api/v1/admin/courses')->assertOk();
        $this->postJson('/api/v1/admin/courses', $this->data())->assertForbidden();
    }
}
