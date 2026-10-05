<?php

namespace App\Http\Requests\Api\V1\DigitalResources;

class UpdateDigitalResourceRequest extends StoreDigitalResourceRequest
{
    public function rules(): array
    {
        return $this->rulesFor($this->route('resource')?->id, true);
    }
}
