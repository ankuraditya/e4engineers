<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->successResponse(NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['email_enabled' => 'sometimes|boolean', 'order_updates' => 'sometimes|boolean', 'payment_updates' => 'sometimes|boolean', 'shipping_updates' => 'sometimes|boolean', 'learning_updates' => 'sometimes|boolean', 'promotional_communications' => 'sometimes|boolean']);
        $preference = NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]);
        $preference->update($data);

        return $this->successResponse($preference->refresh(), 'Notification preferences saved.');
    }
}
