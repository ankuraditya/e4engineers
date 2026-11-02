<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\McqPracticeSet;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class McqPracticeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'subject' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $published = McqPracticeSet::query()->where('status', 'published');
        $subjects = (clone $published)->distinct()->orderBy('subject')->pluck('subject')->all();
        $sets = $published->when($filters['subject'] ?? null, fn ($query, $subject) => $query->where('subject', $subject))
            ->latest()->paginate(12);

        return $this->successResponse($sets->getCollection()->map(fn (McqPracticeSet $set): array => $this->summary($set))->all(),
            'Practice sets retrieved.', meta: [
                'subjects' => $subjects,
                'current_page' => $sets->currentPage(),
                'last_page' => $sets->lastPage(),
                'total' => $sets->total(),
            ]);
    }

    public function show(string $slug): JsonResponse
    {
        $set = McqPracticeSet::query()->where('status', 'published')->where('slug', $slug)->firstOrFail();

        return $this->successResponse($this->summary($set) + [
            'questions' => array_map(fn (array $question): array => [
                'prompt' => $question['prompt'],
                'options' => $question['options'],
            ], $set->questions),
        ], 'Practice set retrieved.');
    }

    public function submit(Request $request, string $slug): JsonResponse
    {
        $set = McqPracticeSet::query()->where('status', 'published')->where('slug', $slug)->firstOrFail();
        $validated = $request->validate([
            'answers' => ['required', 'array', 'list'],
            'answers.*' => ['nullable', 'integer', 'between:0,3'],
        ]);
        $questions = $set->questions;
        if (count($questions) === 0) {
            throw ValidationException::withMessages(['answers' => 'This practice set has no questions yet.']);
        }
        if (count($validated['answers']) !== count($questions)) {
            throw ValidationException::withMessages(['answers' => 'Submit one answer or skip value for every question.']);
        }

        $score = 0;
        $answerKey = [];
        foreach ($questions as $index => $question) {
            $selected = $validated['answers'][$index];
            $correct = $question['correct_option'];
            $isCorrect = $selected !== null && $selected === $correct;
            $score += (int) $isCorrect;
            $answerKey[] = [
                'prompt' => $question['prompt'],
                'options' => $question['options'],
                'selected_option' => $selected,
                'correct_option' => $correct,
                'is_correct' => $isCorrect,
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        return $this->successResponse([
            'score' => $score,
            'total' => count($questions),
            'percentage' => round($score / count($questions) * 100),
            'answer_key' => $answerKey,
        ], 'Practice completed.');
    }

    private function summary(McqPracticeSet $set): array
    {
        return [
            'id' => $set->id,
            'title' => $set->title,
            'slug' => $set->slug,
            'subject' => $set->subject,
            'description' => $set->description,
            'question_count' => count($set->questions),
        ];
    }
}
