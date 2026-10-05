<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderAccessController extends Controller
{
    use ApiResponse;

    public function success(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $owned = $request->user() && $order->user_id === $request->user()->id;
        $token = (string) $request->query('token');
        $guestAccess = $token !== '' && hash_equals((string) $order->guest_access_token_hash, hash('sha256', $token));
        abort_unless($owned || $guestAccess, 404);

        return $this->successResponse(new OrderResource($order->load(['items', 'shippingAddress', 'histories'])));
    }
}
