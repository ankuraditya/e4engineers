<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\UpdateCustomerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateCustomerProfileRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->successResponse(new CustomerResource($request->user()));
    }

    public function update(UpdateCustomerProfileRequest $request, UpdateCustomerProfile $action): JsonResponse
    {
        return $this->successResponse(new CustomerResource($action->execute($request->user(), $request->validated())), 'Profile updated.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => null,
        ])->save();

        return $this->successResponse(null, 'Password updated successfully.');
    }
}
