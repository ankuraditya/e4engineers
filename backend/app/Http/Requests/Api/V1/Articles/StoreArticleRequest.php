<?php

namespace App\Http\Requests\Api\V1\Articles;

use App\Enums\ArticleStatus;
use App\Http\Requests\Api\V1\ApiRequest;
use App\Models\Topic;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends ApiRequest
{
    public function rules(): array
    {
        return $this->rulesFor();
    }

    protected function rulesFor(?int $id = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles')->ignore($id)],
            'excerpt' => ['nullable', 'string', 'max:2000'], 'content' => [$required, 'string', 'max:1000000'],
            'engineering_discipline_id' => [$required, 'integer', 'exists:engineering_disciplines,id'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($q) => $q->whereIn('context', ['article', 'all'])->where('is_active', true))],
            'topic_id' => ['nullable', 'integer', Rule::exists('topics', 'id')->where(fn ($q) => $q->where('is_active', true))],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'], 'author_name' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ArticleStatus::class)], 'is_featured' => ['sometimes', 'boolean'], 'featured_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
            'tag_ids' => ['sometimes', 'array'], 'tag_ids.*' => ['integer', 'distinct', 'exists:tags,id'],
            'contributors' => ['sometimes', 'array'], 'contributors.*.id' => ['required', 'integer', 'distinct', 'exists:contributors,id'],
            'contributors.*.role' => ['sometimes', 'string', Rule::in(['author', 'co-author', 'reviewer', 'technical-contributor'])],
            'contributors.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $topic = $this->input('topic_id');
            $discipline = $this->input('engineering_discipline_id');
            if ($topic && $discipline && ! Topic::whereKey($topic)->where('engineering_discipline_id', $discipline)->exists()) {
                $validator->errors()->add('topic_id', 'The topic must belong to the selected discipline.');
            }
            if (collect($this->input('contributors', []))->where('is_primary', true)->count() > 1) {
                $validator->errors()->add('contributors', 'Only one contributor may be primary.');
            }
        }];
    }
}
