<?php

namespace App\Actions\Books;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Services\BookCache;
use App\Services\HtmlSanitizer;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManageBook
{
    public function __construct(private BookCache $cache, private HtmlSanitizer $sanitizer, private InventoryService $inventory) {}

    public function create(array $data, int $userId): Book
    {
        return $this->save(new Book, $data, $userId, true);
    }

    public function update(Book $book, array $data, int $userId): Book
    {
        return $this->save($book, $data, $userId, false);
    }

    private function save(Book $book, array $data, int $userId, bool $new): Book
    {
        return DB::transaction(function () use ($book, $data, $userId, $new) {
            $authors = $data['authors'] ?? null;
            $gallery = $data['gallery'] ?? null;
            unset($data['authors'],$data['gallery']);
            if (isset($data['description'])) {
                $data['description'] = $this->sanitizer->clean($data['description']);
            }
            if ($new) {
                $data['slug'] ??= Str::slug($data['title']);
                $data['sku'] ??= $this->nextSku();
                $data['created_by'] = $userId;
            }
            $data['updated_by'] = $userId;
            if (($data['status'] ?? null) === BookStatus::Published->value && empty($data['published_at'])) {
                $data['published_at'] = now();
            }
            $book->fill($data)->save();
            if ($new) {
                $this->inventory->initialize($book);
            }
            if ($authors !== null) {
                $pivot = [];
                foreach ($authors as $i => $a) {
                    $pivot[$a['id']] = ['role' => $a['role'] ?? 'author', 'sort_order' => $i];
                } $book->authors()->sync($pivot);
            }
            if ($gallery !== null) {
                $book->images()->delete();
                foreach ($gallery as $i => $image) {
                    $book->images()->create(['media_id' => $image['media_id'], 'sort_order' => $i, 'is_primary' => $image['is_primary'] ?? false]);
                }
            }
            $this->cache->flush();

            return $book->refresh()->load($this->relations());
        });
    }

    private function nextSku(): string
    {
        $next = ((int) Book::withTrashed()->max('id')) + 1;
        do {
            $sku = 'E4E-BOOK-'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (Book::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }

    public function relations(): array
    {
        return ['discipline', 'category', 'publisher.logo', 'cover', 'authors.photo', 'images.media', 'seo.ogMedia', 'inventory'];
    }
}
