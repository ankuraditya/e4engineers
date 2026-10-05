<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\UserStatus;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

final class ChangeAdminUserStatusRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(UserStatus::class)]];
    }
}
