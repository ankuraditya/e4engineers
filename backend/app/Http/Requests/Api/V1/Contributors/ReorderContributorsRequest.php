<?php

namespace App\Http\Requests\Api\V1\Contributors;

use App\Http\Requests\Api\V1\ApiRequest;

class ReorderContributorsRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['items' => ['required', 'array', 'min:1'], 'items.*.id' => ['required', 'integer', 'distinct', 'exists:contributors,id'], 'items.*.sort_order' => ['required', 'integer', 'min:0']];
    }
}
