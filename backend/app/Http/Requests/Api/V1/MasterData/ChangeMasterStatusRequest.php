<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Http\Requests\Api\V1\ApiRequest;

final class ChangeMasterStatusRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
