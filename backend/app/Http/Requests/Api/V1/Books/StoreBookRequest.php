<?php

namespace App\Http\Requests\Api\V1\Books;

use App\Enums\BookFormat;
use App\Enums\BookStatus;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends ApiRequest
{
    public function rules(): array
    {
        return $this->rulesFor();
    }

    protected function rulesFor(?int $id = null, bool $partial = false): array
    {
        $r = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$r, 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('books')->ignore($id)],
            'sku' => ['nullable', 'string', 'max:80', 'regex:/^[A-Z0-9-]+$/', Rule::unique('books')->ignore($id)],
            'isbn' => ['nullable', 'string', 'max:32', Rule::unique('books')->ignore($id)], 'short_description' => ['nullable', 'string', 'max:2000'], 'description' => [$r, 'string', 'max:200000'],
            'engineering_discipline_id' => ['nullable', 'integer', 'exists:engineering_disciplines,id'], 'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($q) => $q->whereIn('context', ['book', 'general', 'all']))],
            'discipline_ids' => ['sometimes', 'array'], 'discipline_ids.*' => ['integer', 'distinct', 'exists:engineering_disciplines,id'],
            'publisher_id' => ['nullable', 'integer', Rule::exists('publishers', 'id')->where(fn ($q) => $q->where('is_active', true))],
            'authors' => ['sometimes', 'array'], 'authors.*.id' => ['required', 'integer', Rule::exists('authors', 'id')->where(fn ($q) => $q->where('is_active', true))], 'authors.*.role' => ['nullable', Rule::in(['author', 'co_author', 'editor'])],
            'edition' => ['nullable', 'string', 'max:100'], 'publication_year' => ['nullable', 'integer', 'min:1000', 'max:'.(now()->year + 2)], 'language' => ['sometimes', 'string', 'max:80'], 'pages' => ['nullable', 'integer', 'min:1'], 'format' => ['sometimes', Rule::enum(BookFormat::class)],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'], 'gallery' => ['sometimes', 'array'], 'gallery.*.media_id' => ['required', 'integer', 'distinct', 'exists:media,id'], 'gallery.*.is_primary' => ['sometimes', 'boolean'],
            'mrp' => [$r, 'numeric', 'min:0'], 'selling_price' => [$r, 'numeric', 'min:0'], 'currency' => ['sometimes', 'string', 'size:3'], 'status' => ['sometimes', Rule::enum(BookStatus::class)],
            'is_featured' => ['sometimes', 'boolean'], 'featured_order' => ['nullable', 'integer', 'min:0'], 'is_new_arrival' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'published_at' => ['nullable', 'date'],
            'weight_grams' => ['nullable', 'integer', 'min:1'], 'length_cm' => ['nullable', 'numeric', 'gt:0'], 'width_cm' => ['nullable', 'numeric', 'gt:0'], 'height_cm' => ['nullable', 'numeric', 'gt:0'], 'shipping_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('isbn')) {
            $this->merge(['isbn' => preg_replace('/[^0-9Xx]/', '', (string) $this->isbn)]);
        }
    }

    public function after(): array
    {
        return [function ($v) {
            $isbn = (string) $this->input('isbn', '');
            if ($isbn !== '' && ! in_array(strlen($isbn), [10, 13], true)) {
                $v->errors()->add('isbn', 'ISBN must contain 10 or 13 characters.');
            } if ((float) $this->input('selling_price', 0) > (float) $this->input('mrp', 0)) {
                $v->errors()->add('selling_price', 'Selling price may not exceed MRP.');
            }
        }];
    }
}
