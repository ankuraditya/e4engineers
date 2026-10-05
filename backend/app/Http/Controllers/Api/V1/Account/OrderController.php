<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\DigitalEntitlement;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ApiResponse;

    public function dashboard(Request $request): JsonResponse
    {
        $orders = Order::query()->where('user_id', $request->user()->id);

        return $this->successResponse([
            'total_orders' => (clone $orders)->count(),
            'orders_in_progress' => (clone $orders)->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])->count(),
            'delivered_orders' => (clone $orders)->where('status', OrderStatus::Delivered->value)->count(),
            'digital_resources' => DigitalEntitlement::query()->forUser($request->user())->valid()->count(),
            'recent_orders' => OrderResource::collection((clone $orders)->with('items')->latest('placed_at')->limit(3)->get())->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['status' => ['nullable', Rule::enum(OrderStatus::class)], 'search' => ['nullable', 'string', 'max:50'], 'per_page' => ['nullable', 'integer', 'between:1,50']]);
        $orders = Order::where('user_id', $request->user()->id)
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['search'] ?? null, fn ($q, $search) => $q->where('order_number', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%'))
            ->with(['items', 'shipment.provider'])->latest('placed_at')->paginate($data['per_page'] ?? 15);

        return $this->successResponse(OrderResource::collection($orders), meta: ['pagination' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()]]);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->where('order_number', $orderNumber)->firstOrFail();

        return $this->successResponse(new OrderResource($order->load(['items', 'shippingAddress', 'histories', 'shipment.provider'])));
    }
}
