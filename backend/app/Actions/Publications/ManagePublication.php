<?php

namespace App\Actions\Publications;

use App\Enums\PublicationStatus;
use App\Models\Publication;
use App\Services\HtmlSanitizer;
use App\Services\PublicationCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManagePublication
{
    public function __construct(private PublicationCache $cache, private HtmlSanitizer $sanitizer) {}

    public function create(array $d, int $u): Publication
    {
        return $this->save(new Publication, $d, $u, true);
    }

    public function update(Publication $p, array $d, int $u): Publication
    {
        return $this->save($p, $d, $u, false);
    }

    private function save(Publication $p, array $d, int $u, bool $new): Publication
    {
        return DB::transaction(function () use ($p, $d, $u, $new) {
            $people = $d['contributors'] ?? null;
            unset($d['contributors']);
            if (isset($d['description'])) {
                $d['description'] = $this->sanitizer->clean($d['description']);
            }if (isset($d['preview_content'])) {
                $d['preview_content'] = $this->sanitizer->clean($d['preview_content']);
            }if (($d['access_type'] ?? null) === 'free') {
                $d['price'] = null;
            }if ($new) {
                $d['slug'] ??= Str::slug($d['title']);
                $d['created_by'] = $u;
            }$d['updated_by'] = $u;
            if (($d['status'] ?? null) === PublicationStatus::Published->value && empty($d['published_at'])) {
                $d['published_at'] = now();
            }$p->fill($d)->save();
            if ($people !== null) {
                $pivot = [];
                foreach ($people as $i => $x) {
                    $pivot[$x['id']] = ['role' => $x['role'], 'sort_order' => $i];
                }$p->contributors()->sync($pivot);
            }$this->cache->flush();

            return $p->refresh()->load($this->relations());
        });
    }

    public function relations(): array
    {
        return ['type', 'discipline', 'category', 'featuredMedia', 'previewMedia', 'fileMedia', 'contributors.media', 'seo.ogMedia'];
    }
}
