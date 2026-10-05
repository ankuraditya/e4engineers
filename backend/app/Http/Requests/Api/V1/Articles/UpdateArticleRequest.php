<?php

namespace App\Http\Requests\Api\V1\Articles;

class UpdateArticleRequest extends StoreArticleRequest
{
    public function rules(): array
    {
        return $this->rulesFor($this->route('article')?->id, true);
    }
}
