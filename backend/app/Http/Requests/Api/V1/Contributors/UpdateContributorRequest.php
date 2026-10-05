<?php

namespace App\Http\Requests\Api\V1\Contributors;

class UpdateContributorRequest extends StoreContributorRequest
{
    public function rules(): array
    {
        return $this->rulesFor((int) $this->route('contributor')?->id, true);
    }
}
