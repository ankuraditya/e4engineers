<?php

namespace App\Http\Requests\Api\V1\Contributors;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreContributorRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->route('contributor') && ! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return $this->rulesFor();
    }

    protected function rulesFor(?int $id = null, bool $partial = false): array
    {
        $p = $partial ? 'sometimes' : 'required';

        return ['name' => [$p, 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('contributors')->ignore($id)], 'designation' => ['nullable', 'string', 'max:255'], 'qualification' => ['nullable', 'string', 'max:500'], 'short_bio' => ['nullable', 'string', 'max:1000'], 'biography' => ['nullable', 'string', 'max:100000'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'media_id' => ['nullable', 'integer', 'exists:media,id'], 'expertise_summary' => ['nullable', 'string', 'max:2000'], 'linkedin_url' => ['nullable', 'url:http,https', 'max:500'], 'website_url' => ['nullable', 'url:http,https', 'max:500'], 'discipline_ids' => [$p, 'array', 'min:1'], 'discipline_ids.*' => ['integer', 'distinct', 'exists:engineering_disciplines,id'], 'primary_discipline_id' => [$p, 'integer', 'exists:engineering_disciplines,id', Rule::in($this->input('discipline_ids', []))], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_featured' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean'], 'published_at' => ['nullable', 'date']];
    }
}
