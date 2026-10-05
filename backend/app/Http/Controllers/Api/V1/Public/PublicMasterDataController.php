<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\MasterDataCache;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class PublicMasterDataController extends Controller
{
    use ApiResponse;

    public function __construct(protected string $type, private MasterDataCache $cache) {}

    public function index(Request $request): JsonResponse
    {
        $configuration = config("master_data.types.{$this->type}");
        $resolver = function () use ($configuration, $request): array {
            $query = $configuration['model']::query()->active();
            $this->applyFilters($query, $request);
            $configuration['ordered'] ? $query->ordered() : $query->orderBy('name')->orderBy('id');

            return $configuration['resource']::collection($query->get())->resolve();
        };
        $hasFilters = $request->query() !== [];
        $data = $configuration['cached'] && ! $hasFilters ? $this->cache->remember($this->type, $resolver) : $resolver();

        return $this->successResponse($data, 'Master data retrieved.');
    }

    public function show(string $slug): JsonResponse
    {
        $configuration = config("master_data.types.{$this->type}");
        $record = $configuration['model']::query()->active()->where('slug', $slug)->firstOrFail();

        return $this->successResponse($configuration['resource']::make($record)->resolve(), 'Master data record retrieved.');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($this->type === 'categories' && $request->filled('type')) {
            $query->where('context', $request->string('type')->toString());
        }
        if ($this->type === 'topics' && $request->filled('discipline')) {
            $query->whereHas('engineeringDiscipline', fn (Builder $disciplineQuery) => $disciplineQuery->where('slug', $request->string('discipline')->toString()));
        }
    }
}
