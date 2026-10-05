<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderStatusService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderStatusService $statuses) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => 'nullable|string|max:100', 'status' => ['nullable', Rule::enum(OrderStatus::class)], 'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)], 'shipping_status' => ['nullable', Rule::enum(ShippingStatus::class)], 'payment_method' => 'nullable|in:cod', 'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from', 'per_page' => 'nullable|integer|between:1,100']);
        $orders = Order::query()->with(['user', 'items'])->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('order_number', 'like', "%$v%")->orWhere('guest_email', 'like', "%$v%")->orWhere('guest_mobile', 'like', "%$v%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%"))))->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($data['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))->when($data['shipping_status'] ?? null, fn ($q, $v) => $q->where('shipping_status', $v))->when($data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('placed_at', '>=', $v))->when($data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('placed_at', '<=', $v))->latest('placed_at')->paginate($data['per_page'] ?? 25);

        return $this->successResponse(OrderResource::collection($orders), meta: ['pagination' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()]]);
    }

    public function show(string $orderNumber): JsonResponse
    {
        return $this->successResponse(new OrderResource(Order::where('order_number', $orderNumber)->with(['items', 'shippingAddress', 'histories'])->firstOrFail()));
    }

    public function status(Request $request, string $orderNumber): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)], 'note' => 'nullable|string|max:500']);
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return $this->successResponse(new OrderResource($this->statuses->transition($order, OrderStatus::from($data['status']), $request->user()->id, $data['note'] ?? null)->load(['items', 'shippingAddress', 'histories'])), 'Order status updated.');
    }
}
