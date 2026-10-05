<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class BookResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $detail = $request->route('slug') !== null;
        $mrp = (float) $this->mrp;
        $price = (float) $this->selling_price;
        $saving = max(0, $mrp - $price);

        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'sku' => $this->sku, 'isbn' => $this->isbn, 'short_description' => $this->short_description, 'description' => $this->when($detail, $this->description),
            'discipline' => $this->discipline ? ['id' => $this->discipline->id, 'name' => $this->discipline->name, 'slug' => $this->discipline->slug] : null, 'category' => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug] : null,
            'authors' => $this->authors->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'slug' => $a->slug, 'role' => $a->pivot->role])->values(), 'author_summary' => $this->authors->pluck('name')->join(', '),
            'publisher' => $this->publisher ? ['id' => $this->publisher->id, 'name' => $this->publisher->name, 'slug' => $this->publisher->slug] : null, 'edition' => $this->edition, 'publication_year' => $this->publication_year, 'language' => $this->language, 'pages' => $this->pages, 'format' => $this->format->value,
            'cover' => $this->cover ? ['url' => $this->cover->url, 'alt_text' => $this->cover->alt_text, 'metadata' => $this->cover->metadata] : null, 'gallery' => $this->when($detail, $this->images->map(fn ($i) => ['id' => $i->id, 'url' => $i->media->url, 'alt_text' => $i->media->alt_text, 'sort_order' => $i->sort_order, 'is_primary' => $i->is_primary])->values()),
            'mrp' => $this->mrp, 'selling_price' => $this->selling_price, 'price' => $this->selling_price, 'currency' => $this->currency, 'saving_amount' => number_format($saving, 2, '.', ''), 'discount_percentage' => $mrp > 0 ? round(($saving / $mrp) * 100, 2) : 0,
            'is_featured' => $this->is_featured, 'is_new_arrival' => $this->is_new_arrival, 'inventory' => ['status' => $this->inventory?->status ?? 'OUT_OF_STOCK', 'is_available' => ($this->inventory?->available_quantity ?? 0) > 0, 'is_low_stock' => ($this->inventory?->status ?? null) === 'LOW_STOCK'], 'seo' => $this->whenLoaded('seo', fn () => new SeoResource($this->seo))];
    }
}
