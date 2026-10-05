<?php

namespace App\Http\Requests\Api\V1\Cms;

use App\Http\Requests\Api\V1\ApiRequest;

class ReorderRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['items' => ['required', 'array', 'min:1'], 'items.*.id' => ['required', 'integer', 'distinct'], 'items.*.sort_order' => ['required', 'integer', 'min:0']];
    }
}
