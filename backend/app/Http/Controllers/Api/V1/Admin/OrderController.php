<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\ReferralService;
use App\Services\OrderStatusService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function collectCod(string $orderNumber, ReferralService $referrals): JsonResponse
    {
        $order = DB::transaction(function () use ($orderNumber, $referrals): Order {
            $order = Order::query()->where('order_number', $orderNumber)->lockForUpdate()->firstOrFail();
            abort_unless($order->payment_method === 'cod' && $order->status === OrderStatus::Delivered, 422, 'Only delivered COD orders can be marked collected.');
            if ($order->payment_status === PaymentStatus::Paid) {
                return $order;
            }
            abort_unless($order->payment_status === PaymentStatus::CodPending, 422, 'This COD payment is not pending.');
            $order->update(['payment_status' => PaymentStatus::Paid]);
            DB::table('payment_attempts')->where('order_id', $order->id)->where('status', 'cod_pending')->update(['status' => 'succeeded', 'updated_at' => now()]);
            DB::table('payment_transactions')->where('order_id', $order->id)->where('provider_code', 'COD')->where('status', 'pending')->update(['status' => 'succeeded', 'updated_at' => now()]);
            $order->histories()->create(['status_type' => 'payment', 'from_status' => PaymentStatus::CodPending->value, 'to_status' => PaymentStatus::Paid->value, 'note' => 'Cash on delivery collection confirmed by administrator']);
            $referrals->awardForPaidOrder($order);

            return $order->refresh();
        });

        return $this->successResponse(new OrderResource($order), 'COD collection confirmed.');
    }
}
