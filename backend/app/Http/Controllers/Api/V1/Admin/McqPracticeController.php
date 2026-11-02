<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\McqPracticeSet;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class McqPracticeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('courses.view');
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $sets = McqPracticeSet::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%")))
            ->latest()->paginate(20);

        return $this->successResponse($sets->getCollection()->map(fn (McqPracticeSet $set): array => $this->record($set))->all(),
            'Practice sets retrieved.', meta: ['current_page' => $sets->currentPage(), 'last_page' => $sets->lastPage(), 'total' => $sets->total()]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('courses.create');
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        if ($data['slug'] === '') {
            throw ValidationException::withMessages(['title' => 'Use a title that can form a URL.']);
        }
        if (McqPracticeSet::withTrashed()->where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages(['title' => 'A practice set with this title already exists.']);
        }
        $data['created_by'] = $request->user()->id;
        $set = McqPracticeSet::create($data);

        return $this->successResponse($this->record($set), 'Practice set created.', 201);
    }

    public function show(McqPracticeSet $set): JsonResponse
    {
        Gate::authorize('courses.view');

        return $this->successResponse($this->record($set));
    }

    public function update(Request $request, McqPracticeSet $set): JsonResponse
    {
        Gate::authorize('courses.update');
        $data = $this->validated($request, $set);
        if (isset($data['slug']) && McqPracticeSet::withTrashed()->where('slug', $data['slug'])->whereKeyNot($set->id)->exists()) {
            throw ValidationException::withMessages(['slug' => 'This URL slug is already in use.']);
        }
        $set->update($data);

        return $this->successResponse($this->record($set->refresh()), 'Practice set updated.');
    }

    public function destroy(McqPracticeSet $set): JsonResponse
    {
        Gate::authorize('courses.delete');
        $set->delete();

        return $this->successResponse(null, 'Practice set archived.');
    }

    private function validated(Request $request, ?McqPracticeSet $set = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'alpha_dash', 'max:180', Rule::unique('mcq_practice_sets', 'slug')->ignore($set?->id)],
            'subject' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'questions' => ['required', 'array', 'list', 'max:100'],
            'questions.*' => ['required', 'array:prompt,options,correct_option,explanation'],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array', 'list', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_option' => ['required', 'integer', 'between:0,3'],
            'questions.*.explanation' => ['nullable', 'string', 'max:3000'],
        ]);
        if ($data['status'] === 'published' && count($data['questions']) === 0) {
            throw ValidationException::withMessages(['questions' => 'Add at least one question before publishing.']);
        }
        if ($data['status'] === 'published') {
            Gate::authorize('courses.publish');
        }

        return $data;
    }

    private function record(McqPracticeSet $set): array
    {
        return $set->only(['id', 'title', 'slug', 'subject', 'description', 'status', 'questions', 'created_at', 'updated_at'])
            + ['question_count' => count($set->questions)];
    }
}
