<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\MasterData\ManageMasterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\ChangeMasterStatusRequest;
use App\Http\Requests\Api\V1\MasterData\ReorderMasterDataRequest;
use App\Http\Requests\Api\V1\MasterData\StoreMasterDataRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateMasterDataRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

abstract class AdminMasterDataController extends Controller
{
    use ApiResponse;

    public function __construct(protected string $type) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize("{$this->type}.view");
        $configuration = config("master_data.types.{$this->type}");
        $query = $configuration['model']::query();
        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }
        $configuration['ordered'] ? $query->ordered() : $query->orderBy('name')->orderBy('id');

        return $this->successResponse($configuration['resource']::collection($query->get())->resolve(), 'Master data retrieved.');
    }

    public function store(StoreMasterDataRequest $request, ManageMasterData $manager): JsonResponse
    {
        Gate::authorize("{$this->type}.create");
        $configuration = config("master_data.types.{$this->type}");
        $record = $manager->create($this->type, $configuration['model'], $request->validated());

        return $this->successResponse($configuration['resource']::make($record)->resolve(), 'Master data created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize("{$this->type}.view");
        $configuration = config("master_data.types.{$this->type}");
        $record = $configuration['model']::query()->findOrFail($id);

        return $this->successResponse($configuration['resource']::make($record)->resolve(), 'Master data record retrieved.');
    }

    public function update(UpdateMasterDataRequest $request, int $id, ManageMasterData $manager): JsonResponse
    {
        Gate::authorize("{$this->type}.update");
        $configuration = config("master_data.types.{$this->type}");
        $record = $configuration['model']::query()->findOrFail($id);
        $record = $manager->update($this->type, $record, $request->validated());

        return $this->successResponse($configuration['resource']::make($record)->resolve(), 'Master data updated successfully.');
    }

    public function status(ChangeMasterStatusRequest $request, int $id, ManageMasterData $manager): JsonResponse
    {
        Gate::authorize("{$this->type}.update");
        $configuration = config("master_data.types.{$this->type}");
        $record = $configuration['model']::query()->findOrFail($id);
        $record = $manager->status($this->type, $record, $request->boolean('is_active'));

        return $this->successResponse($configuration['resource']::make($record)->resolve(), 'Master data status updated successfully.');
    }

    public function reorder(ReorderMasterDataRequest $request, ManageMasterData $manager): JsonResponse
    {
        $configuration = config("master_data.types.{$this->type}");
        abort_unless($configuration['ordered'], 404);
        $permission = in_array($this->type, ['engineering-disciplines', 'categories', 'topics'], true) ? "{$this->type}.reorder" : "{$this->type}.update";
        Gate::authorize($permission);
        $manager->reorder($this->type, $configuration['model'], $request->validated('items'));

        return $this->successResponse(null, 'Master data reordered successfully.');
    }
}
