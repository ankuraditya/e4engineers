<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AdminLoginRequest;
use App\Http\Resources\Api\V1\AdminUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class AdminAuthenticationController extends Controller
{
    use ApiResponse;

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password) || $user->status !== UserStatus::Active || ! $user->isAdmin()) {
            Log::warning('Administrative login failed.', [
                'matched_user_id' => $user?->id,
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]);

            return $this->errorResponse('Invalid administrative credentials.', status: 401);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        Log::notice('Administrative login succeeded.', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return $this->successResponse(['user' => AdminUserResource::make($user)->resolve()], 'Administrative login successful.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(['user' => AdminUserResource::make($request->user())->resolve()], 'Authenticated administrator retrieved.');
    }

    public function logout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::notice('Administrative logout succeeded.', ['user_id' => $userId, 'ip' => $request->ip()]);

        return $this->successResponse(null, 'Administrative logout successful.');
    }
}
