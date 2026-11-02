<?php

namespace Tests\Feature;

use App\Models\McqPracticeSet;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class McqPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(AuthorizationSeeder::class);
    }

    private function payload(string $status = 'draft'): array
    {
        return [
            'title' => 'Circuit Fundamentals',
            'subject' => 'Electrical Engineering',
            'description' => 'Practice basic circuit concepts.',
            'status' => $status,
            'questions' => [[
                'prompt' => 'Which unit measures resistance?',
                'options' => ['Ampere', 'Ohm', 'Volt', 'Watt'],
                'correct_option' => 1,
                'explanation' => 'Resistance is measured in ohms.',
            ]],
        ];
    }

    public function test_admin_publishes_a_set_and_students_receive_answers_only_after_submission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/mcq-practice', $this->payload('published'))
            ->assertCreated()->assertJsonPath('data.slug', 'circuit-fundamentals');

        $this->getJson('/api/v1/mcq-practice')->assertOk()
            ->assertJsonPath('meta.subjects.0', 'Electrical Engineering')
            ->assertJsonPath('data.0.question_count', 1);
        $this->getJson('/api/v1/mcq-practice/circuit-fundamentals')->assertOk()
            ->assertJsonPath('data.questions.0.prompt', 'Which unit measures resistance?')
            ->assertJsonMissingPath('data.questions.0.correct_option');

        $this->postJson('/api/v1/mcq-practice/circuit-fundamentals/submit', ['answers' => [1]])
            ->assertOk()->assertJsonPath('data.score', 1)
            ->assertJsonPath('data.answer_key.0.correct_option', 1);
        $this->postJson('/api/v1/mcq-practice/circuit-fundamentals/submit', ['answers' => [0]])
            ->assertOk()->assertJsonPath('data.score', 0);
    }

    public function test_drafts_are_hidden_and_customers_cannot_manage_sets(): void
    {
        McqPracticeSet::create($this->payload() + ['slug' => 'circuit-fundamentals']);
        $this->getJson('/api/v1/mcq-practice')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/mcq-practice/circuit-fundamentals')->assertNotFound();

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/mcq-practice')->assertForbidden();
    }

    public function test_published_set_requires_questions_and_submission_requires_matching_answer_count(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/mcq-practice', array_replace($this->payload('published'), ['questions' => []]))
            ->assertUnprocessable()->assertJsonValidationErrors('questions');

        $set = McqPracticeSet::create($this->payload('published') + ['slug' => 'circuit-fundamentals']);
        $this->postJson("/api/v1/mcq-practice/{$set->slug}/submit", ['answers' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('answers');
    }
}
