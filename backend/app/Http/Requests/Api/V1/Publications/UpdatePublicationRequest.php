<?php

namespace App\Http\Requests\Api\V1\Publications;

class UpdatePublicationRequest extends StorePublicationRequest
{
    public function rules(): array
    {
        return $this->rulesFor($this->route('publication')?->id, true);
    }
}
