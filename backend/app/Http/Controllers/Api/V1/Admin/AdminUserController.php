<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\ChangeAdminUserStatus;
use App\Actions\Admin\CreateAdminUser;
use App\Actions\Admin\UpdateAdminUser;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ChangeAdminUserStatusRequest;
use App\Http\Requests\Api\V1\Admin\StoreAdminUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAdminUserRequest;
use App\Http\Resources\Api\V1\AdminUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class AdminUserController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $users = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', config('admin_authorization.admin_roles')))
            ->with('roles', 'permissions')
            ->orderBy('name')->orderBy('id')
            ->paginate(20);

        return $this->successResponse(
            AdminUserResource::collection($users->items())->resolve(),
            'Administrative users retrieved.',
            meta: ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'per_page' => $users->perPage(), 'total' => $users->total()],
        );
    }

    public function store(StoreAdminUserRequest $request, CreateAdminUser $createAdminUser): JsonResponse
    {
        Gate::authorize('create', User::class);
        $user = $createAdminUser->handle($request->user(), $request->validated());

        return $this->successResponse(AdminUserResource::make($user)->resolve(), 'Administrative user created successfully.', 201);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return $this->successResponse(AdminUserResource::make($user->load('roles', 'permissions'))->resolve(), 'Administrative user retrieved.');
    }

    public function update(UpdateAdminUserRequest $request, User $user, UpdateAdminUser $updateAdminUser): JsonResponse
    {
        Gate::authorize('update', $user);
        $user = $updateAdminUser->handle($request->user(), $user, $request->validated());

        return $this->successResponse(AdminUserResource::make($user)->resolve(), 'Administrative user updated successfully.');
    }

    public function status(ChangeAdminUserStatusRequest $request, User $user, ChangeAdminUserStatus $changeStatus): JsonResponse
    {
        Gate::authorize('changeStatus', $user);
        $status = UserStatus::from($request->validated('status'));
        Gate::authorize($status === UserStatus::Active ? 'admin-users.activate' : 'admin-users.suspend');
        $user = $changeStatus->handle($request->user(), $user, $status);

        return $this->successResponse(AdminUserResource::make($user)->resolve(), 'Administrative user status updated successfully.');
    }
}
