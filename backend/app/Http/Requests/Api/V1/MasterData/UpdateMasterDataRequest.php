<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

final class UpdateMasterDataRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = (string) $this->route('master_type');
        $table = config("master_data.types.{$type}.table");
        $id = $this->route('id');
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, 'slug')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($type !== 'tags') {
            $rules['sort_order'] = ['sometimes', 'integer', 'min:0'];
        }
        if (in_array($type, ['engineering-disciplines', 'categories', 'topics', 'resource-types', 'publication-types'], true)) {
            $rules['description'] = ['sometimes', 'nullable', 'string'];
        }
        if ($type === 'engineering-disciplines') {
            $rules += ['short_name' => ['sometimes', 'nullable', 'string', 'max:50'], 'icon' => ['sometimes', 'nullable', 'string', 'max:255'], 'image' => ['sometimes', 'nullable', 'string', 'max:255']];
        }
        if ($type === 'categories') {
            $rules['context'] = ['sometimes', 'required', Rule::in(config('master_data.categories_contexts'))];
        }
        if ($type === 'topics') {
            $rules += [
                'engineering_discipline_id' => ['sometimes', 'nullable', 'integer', 'exists:engineering_disciplines,id'],
                'parent_id' => ['sometimes', 'nullable', 'integer', 'different:id', Rule::exists('topics', 'id')->whereNot('id', $id)],
            ];
        }

        return $rules;
    }
}
