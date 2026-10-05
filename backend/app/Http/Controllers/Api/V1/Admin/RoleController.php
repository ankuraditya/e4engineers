<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\SyncRolePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRoleRequest;
use App\Http\Requests\Api\V1\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRoleRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);
        $roles = Role::query()->with('permissions')->orderBy('name')->get();

        return $this->successResponse($roles->map(fn (Role $role): array => $this->serializeRole($role))->all(), 'Roles retrieved.');
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        Gate::authorize('create', Role::class);
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->successResponse($this->serializeRole($role), 'Role created successfully.', 201);
    }

    public function show(Role $role): JsonResponse
    {
        Gate::authorize('view', $role);

        return $this->successResponse($this->serializeRole($role->load('permissions')), 'Role retrieved.');
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        Gate::authorize('update', $role);
        $role->update(['name' => $request->validated('name')]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->successResponse($this->serializeRole($role->load('permissions')), 'Role updated successfully.');
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissions $syncRolePermissions): JsonResponse
    {
        Gate::authorize('syncPermissions', $role);
        $role = $syncRolePermissions->handle($role, $request->validated('permissions'));

        return $this->successResponse($this->serializeRole($role), 'Role permissions updated successfully.');
    }

    /** @return array<string, mixed> */
    private function serializeRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'system' => in_array($role->name, config('admin_authorization.roles'), true),
            'protected' => in_array($role->name, config('admin_authorization.protected_roles'), true),
            'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
        ];
    }
}
