<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

final class ReorderMasterDataRequest extends ApiRequest
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

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists($table, 'id')],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'distinct'],
        ];
    }
}
