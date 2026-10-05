<?php

namespace App\Actions\MasterData;

use App\Models\MasterData;
use App\Services\MasterDataCache;
use Illuminate\Support\Facades\DB;

final class ManageMasterData
{
    public function __construct(private MasterDataCache $cache) {}

    /** @param class-string<MasterData> $modelClass */
    public function create(string $type, string $modelClass, array $data): MasterData
    {
        $record = $modelClass::query()->create($data);
        $this->cache->forget($type);

        return $record;
    }

    public function update(string $type, MasterData $record, array $data): MasterData
    {
        $record->update($data);
        $this->cache->forget($type);

        return $record->refresh();
    }

    public function status(string $type, MasterData $record, bool $isActive): MasterData
    {
        return $this->update($type, $record, ['is_active' => $isActive]);
    }

    /** @param class-string<MasterData> $modelClass */
    public function reorder(string $type, string $modelClass, array $items): void
    {
        DB::transaction(function () use ($modelClass, $items): void {
            foreach ($items as $item) {
                $modelClass::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });
        $this->cache->forget($type);
    }
}
