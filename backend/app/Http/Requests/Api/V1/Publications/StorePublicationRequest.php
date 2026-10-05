<?php

namespace App\Http\Requests\Api\V1\Publications;

use App\Enums\PublicationAccessType;
use App\Enums\PublicationPreviewType;
use App\Enums\PublicationStatus;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StorePublicationRequest extends ApiRequest
{
    public function rules(): array
    {
        return $this->rulesFor();
    }

    protected function rulesFor(?int $id = null, bool $partial = false): array
    {
        $r = $partial ? 'sometimes' : 'required';

        return ['title' => [$r, 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('publications')->ignore($id)], 'short_description' => ['nullable', 'string', 'max:2000'], 'description' => [$r, 'string', 'max:200000'], 'publication_type_id' => [$r, 'integer', 'exists:publication_types,id'], 'engineering_discipline_id' => ['nullable', 'integer', 'exists:engineering_disciplines,id'], 'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($q) => $q->whereIn('context', ['publication', 'general', 'all']))], 'featured_media_id' => ['nullable', 'integer', 'exists:media,id'], 'author_text' => ['nullable', 'string', 'max:1000'], 'editor_text' => ['nullable', 'string', 'max:1000'], 'publication_date' => ['nullable', 'date'], 'volume' => ['nullable', 'string', 'max:100'], 'issue' => ['nullable', 'string', 'max:100'], 'pages' => ['nullable', 'integer', 'min:1'], 'access_type' => [$r, Rule::enum(PublicationAccessType::class)], 'price' => ['nullable', 'numeric', 'min:0', 'required_if:access_type,paid'], 'currency' => ['sometimes', 'string', 'size:3'], 'preview_type' => ['sometimes', Rule::enum(PublicationPreviewType::class)], 'preview_content' => ['nullable', 'string', 'max:50000', 'required_if:preview_type,text'], 'preview_media_id' => ['nullable', 'integer', 'exists:media,id', 'required_if:preview_type,media'], 'file_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where(fn ($q) => $q->where('disk', 'private')->whereIn('extension', ['pdf', 'doc', 'docx', 'zip']))], 'status' => ['sometimes', Rule::enum(PublicationStatus::class)], 'is_featured' => ['sometimes', 'boolean'], 'featured_order' => ['nullable', 'integer', 'min:0'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'contributors' => ['sometimes', 'array'], 'contributors.*.id' => ['required', 'integer', 'exists:contributors,id'], 'contributors.*.role' => ['required', Rule::in(['author', 'editor', 'reviewer', 'contributor'])]];
    }

    public function after(): array
    {
        return [function ($v) {
            if ($this->input('access_type') === 'paid' && (float) $this->input('price', 0) <= 0) {
                $v->errors()->add('price', 'A paid publication requires a positive price.');
            }
        }];
    }
}
