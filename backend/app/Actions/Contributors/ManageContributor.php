<?php

namespace App\Actions\Contributors;

use App\Models\Contributor;
use App\Services\ContributorCache;
use Illuminate\Support\Facades\DB;

final class ManageContributor
{
    public function __construct(private ContributorCache $cache) {}

    public function create(array $data, int $userId): Contributor
    {
        return DB::transaction(function () use ($data, $userId) {
            $disciplines = $this->disciplines($data);
            $model = Contributor::create($data + ['created_by' => $userId, 'updated_by' => $userId]);
            $this->sync($model, $disciplines);
            $this->cache->flush();

            return $model->load(['media', 'disciplines', 'seo.ogMedia']);
        });
    }

    public function update(Contributor $model, array $data, int $userId): Contributor
    {
        return DB::transaction(function () use ($model, $data, $userId) {
            $disciplines = $this->disciplines($data, false);
            $model->update($data + ['updated_by' => $userId]);
            if ($disciplines !== null) {
                $this->sync($model, $disciplines);
            }$this->cache->flush();

            return $model->refresh()->load(['media', 'disciplines', 'seo.ogMedia']);
        });
    }

    public function reorder(array $items): void
    {
        DB::transaction(fn () => collect($items)->each(fn ($i) => Contributor::whereKey($i['id'])->update(['sort_order' => $i['sort_order']])));
        $this->cache->flush();
    }

    private function disciplines(array &$data, bool $required = true): ?array
    {
        $ids = $data['discipline_ids'] ?? null;
        $primary = $data['primary_discipline_id'] ?? null;
        unset($data['discipline_ids'],$data['primary_discipline_id']);
        if ($ids === null && ! $required) {
            return null;
        }

return ['ids' => $ids ?? [], 'primary' => $primary];
    }

    private function sync(Contributor $model, array $value): void
    {
        $payload = [];
        foreach ($value['ids'] as $order => $id) {
            $payload[$id] = ['is_primary' => (int) $id === (int) $value['primary'], 'sort_order' => $order];
        }$model->disciplines()->sync($payload);
    }
}
