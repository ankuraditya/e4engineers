<?php

namespace App\Http\Requests\Api\V1\Books;

class UpdateBookRequest extends StoreBookRequest
{
    public function rules(): array
    {
        return $this->rulesFor((int) $this->route('book')?->id, true);
    }
}
