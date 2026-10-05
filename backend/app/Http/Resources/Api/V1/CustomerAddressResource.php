<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class CustomerAddressResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'type' => $this->type->value, 'full_name' => $this->full_name, 'mobile' => $this->mobile, 'address_line_1' => $this->address_line_1, 'address_line_2' => $this->address_line_2, 'landmark' => $this->landmark, 'city' => $this->city, 'state' => $this->state, 'postal_code' => $this->postal_code, 'country_code' => $this->country_code, 'is_default' => $this->is_default];
    }
}
