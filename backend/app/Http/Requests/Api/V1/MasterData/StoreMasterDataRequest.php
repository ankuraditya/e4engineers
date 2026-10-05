<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreMasterDataRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = (string) $this->route('master_type');
        $table = config("master_data.types.{$type}.table");
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, 'slug')],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($type !== 'tags') {
            $rules['sort_order'] = ['sometimes', 'integer', 'min:0'];
        }
        if (in_array($type, ['engineering-disciplines', 'categories', 'topics', 'resource-types', 'publication-types'], true)) {
            $rules['description'] = ['nullable', 'string'];
        }
        if ($type === 'engineering-disciplines') {
            $rules += ['short_name' => ['nullable', 'string', 'max:50'], 'icon' => ['nullable', 'string', 'max:255'], 'image' => ['nullable', 'string', 'max:255']];
        }
        if ($type === 'categories') {
            $rules['context'] = ['required', Rule::in(config('master_data.categories_contexts'))];
        }
        if ($type === 'topics') {
            $rules += [
                'engineering_discipline_id' => ['nullable', 'integer', 'exists:engineering_disciplines,id'],
                'parent_id' => ['nullable', 'integer', 'exists:topics,id'],
            ];
        }

        return $rules;
    }
}
