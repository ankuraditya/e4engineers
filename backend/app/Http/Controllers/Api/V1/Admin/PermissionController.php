<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

final class PermissionController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        Gate::authorize('permissions.view');
        $permissions = Permission::query()->orderBy('name')->pluck('name')->all();

        return $this->successResponse($permissions, 'Permissions retrieved.');
    }
}
