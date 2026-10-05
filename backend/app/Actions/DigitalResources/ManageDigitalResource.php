<?php

namespace App\Actions\DigitalResources;

use App\Enums\DigitalResourceStatus;
use App\Models\DigitalResource;
use App\Models\Media;
use App\Services\DigitalResourceCache;
use App\Services\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManageDigitalResource
{
    public function __construct(private HtmlSanitizer $sanitizer, private DigitalResourceCache $cache) {}

    public function relations(): array
    {
        return ['type', 'discipline', 'category', 'topic', 'tags', 'thumbnail', 'previewMedia', 'fileMedia', 'seo.ogMedia'];
    }

    public function create(array $data, int $userId): DigitalResource
    {
        return DB::transaction(function () use ($data, $userId): DigitalResource {
            $tags = Arr::pull($data, 'tag_ids', []);
            $data = $this->normalize($data);
            $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['title']);
            $data['created_by'] = $data['updated_by'] = $userId;
            $resource = DigitalResource::create($data);
            $resource->tags()->sync($tags);
            $this->cache->flush();

            return $resource->load($this->relations());
        });
    }

    public function update(DigitalResource $resource, array $data, int $userId): DigitalResource
    {
        return DB::transaction(function () use ($resource, $data, $userId): DigitalResource {
            $tags = Arr::pull($data, 'tag_ids', null);
            $data = $this->normalize($data, $resource);
            $data['updated_by'] = $userId;
            $resource->update($data);
            if ($tags !== null) {
                $resource->tags()->sync($tags);
            }
            $this->cache->flush();

            return $resource->fresh($this->relations());
        });
    }

    private function normalize(array $data, ?DigitalResource $resource = null): array
    {
        foreach (['description', 'preview_content'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->sanitizer->clean($data[$field]);
            }
        }
        $access = $data['access_type'] ?? $resource?->access_type?->value;
        if ($access !== 'paid') {
            $data['price'] = null;
        }
        if (($data['status'] ?? null) === DigitalResourceStatus::Published->value && ! ($data['published_at'] ?? $resource?->published_at)) {
            $data['published_at'] = now();
        }
        if (array_key_exists('file_media_id', $data) && $data['file_media_id']) {
            $media = Media::find($data['file_media_id']);
            $data['file_format'] = strtoupper($media?->extension ?? '');
            $data['file_size'] = $media?->size;
        }

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while (DigitalResource::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
