<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginCustomer;
use App\Actions\Auth\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthenticationController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request, RegisterCustomer $registerCustomer): JsonResponse
    {
        $user = $registerCustomer->handle($request->validated());
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $this->successResponse([
            'user' => CustomerResource::make($user)->resolve(),
        ], 'Account created successfully.', 201);
    }

    public function login(LoginRequest $request, LoginCustomer $loginCustomer): JsonResponse
    {
        $validated = $request->validated();
        $user = $loginCustomer->handle(
            $validated['login'],
            $validated['password'],
            (bool) ($validated['remember'] ?? false),
        );

        if ($user === null) {
            return $this->errorResponse('Invalid login credentials.', status: 401);
        }

        $request->session()->regenerate();

        return $this->successResponse([
            'user' => CustomerResource::make($user->fresh())->resolve(),
        ], 'Login successful.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse([
            'user' => CustomerResource::make($request->user())->resolve(),
        ], 'Authenticated customer retrieved.');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->successResponse(null, 'Logout successful.');
    }
}
