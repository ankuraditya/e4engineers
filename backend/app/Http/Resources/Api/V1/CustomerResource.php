<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

final class CustomerResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'mobile_verified_at' => $this->mobile_verified_at?->toISOString(),
            'status' => $this->status->value,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
