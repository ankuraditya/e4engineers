<?php

namespace App\Http\Requests\Api\V1\DigitalResources;

use App\Enums\DigitalResourceAccessType;
use App\Enums\DigitalResourcePreviewType;
use App\Enums\DigitalResourceStatus;
use App\Http\Requests\Api\V1\ApiRequest;
use App\Models\Topic;
use Illuminate\Validation\Rule;

class StoreDigitalResourceRequest extends ApiRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('digital_resources')->ignore($id)],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'description' => [$required, 'string', 'max:200000'],
            'resource_type_id' => [$required, 'integer', 'exists:resource_types,id'],
            'engineering_discipline_id' => [$required, 'integer', 'exists:engineering_disciplines,id'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($q) => $q->whereIn('context', ['resource', 'general', 'all']))],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'tag_ids' => ['sometimes', 'array'], 'tag_ids.*' => ['integer', 'distinct', 'exists:tags,id'],
            'thumbnail_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where(fn ($q) => $q->where('disk', 'public')->where('mime_type', 'like', 'image/%'))],
            'preview_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where(fn ($q) => $q->where('disk', 'public'))],
            'file_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where(fn ($q) => $q->where('disk', 'private')->whereIn('extension', ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip']))],
            'access_type' => [$required, Rule::enum(DigitalResourceAccessType::class)],
            'price' => ['nullable', 'numeric', 'min:0', 'required_if:access_type,paid'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'pages' => ['nullable', 'integer', 'min:1'], 'version' => ['nullable', 'string', 'max:100'],
            'preview_type' => ['sometimes', Rule::enum(DigitalResourcePreviewType::class)],
            'preview_content' => ['nullable', 'string', 'max:50000', 'required_if:preview_type,text'],
            'status' => ['sometimes', Rule::enum(DigitalResourceStatus::class)],
            'is_featured' => ['sometimes', 'boolean'], 'featured_order' => ['nullable', 'integer', 'min:0'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'published_at' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->input('access_type') === 'paid' && (float) $this->input('price', 0) <= 0) {
                $validator->errors()->add('price', 'A paid resource requires a positive price.');
            }
            $resource = $this->route('resource');
            $access = $this->input('access_type', $resource?->access_type?->value);
            $status = $this->input('status', $resource?->status?->value);
            $fileId = $this->input('file_media_id', $resource?->file_media_id);
            if ($access === 'paid' && $status === 'published' && ! $fileId) {
                $validator->errors()->add('file_media_id', 'A paid resource must reference a private file before publication.');
            }
            if ($this->filled('topic_id') && $this->filled('engineering_discipline_id') && ! Topic::whereKey($this->integer('topic_id'))->where('engineering_discipline_id', $this->integer('engineering_discipline_id'))->exists()) {
                $validator->errors()->add('topic_id', 'The topic must belong to the selected engineering discipline.');
            }
        }];
    }
}
