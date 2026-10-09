<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\ReferralService;
use App\Services\OrderStatusService;
use App\Services\OperationalNotificationService;
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
        $data = $request->validate(['search' => 'nullable|string|max:100', 'delivery_method' => 'nullable|in:courier,self_collect', 'status' => ['nullable', Rule::enum(OrderStatus::class)], 'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)], 'shipping_status' => ['nullable', Rule::enum(ShippingStatus::class)], 'payment_method' => 'nullable|in:cod', 'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from', 'per_page' => 'nullable|integer|between:1,100']);
        $orders = Order::query()->with(['user', 'items'])->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('order_number', 'like', "%$v%")->orWhere('guest_email', 'like', "%$v%")->orWhere('guest_mobile', 'like', "%$v%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%"))))->when($data['delivery_method'] ?? null, fn ($q, $v) => $q->where('delivery_method', $v))->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($data['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))->when($data['shipping_status'] ?? null, fn ($q, $v) => $q->where('shipping_status', $v))->when($data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('placed_at', '>=', $v))->when($data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('placed_at', '<=', $v))->latest('placed_at')->paginate($data['per_page'] ?? 25);

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

    public function pickupReady(Request $request, string $orderNumber): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $orderNumber): Order {
            $order = Order::query()->where('order_number', $orderNumber)->lockForUpdate()->firstOrFail();
            abort_unless($order->delivery_method === 'self_collect', 422, 'This is not a Self Collect order.');
            abort_unless($order->payment_method === 'cod' || $order->payment_status === PaymentStatus::Paid, 422, 'Verify payment before preparing collection.');
            abort_unless(in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Packed], true), 422, 'This order cannot be prepared for collection.');
            if (! $order->pickup_ready_at) {
                $from = $order->status;
                $order->update(['status' => OrderStatus::Packed, 'pickup_ready_at' => now()]);
                $order->histories()->create(['status_type' => 'order', 'from_status' => $from->value, 'to_status' => OrderStatus::Packed->value, 'note' => 'Ready for customer collection', 'changed_by' => $request->user()->id]);
            }

            return $order->refresh();
        });

        if ($order->user?->email || $order->guest_email) {
            app(OperationalNotificationService::class)->customer(NotificationType::SelfCollectReady, $order, (string) ($order->user?->email ?: $order->guest_email), [
                'order_number' => $order->order_number,
                'pickup_name' => $order->shipping_snapshot['pickup']['name'] ?? 'E4ENGINEERS',
                'pickup_address' => implode(', ', array_filter([$order->shipping_snapshot['pickup']['address_line_1'] ?? null, $order->shipping_snapshot['pickup']['city'] ?? null, $order->shipping_snapshot['pickup']['state'] ?? null, $order->shipping_snapshot['pickup']['postal_code'] ?? null])),
                'pickup_hours' => $order->shipping_snapshot['pickup']['hours'] ?? '',
                'pickup_phone' => $order->shipping_snapshot['pickup']['phone'] ?? '',
                'order_url' => rtrim((string) config('e4engineers.frontend_url'), '/').'/account/orders/'.$order->order_number,
            ]);
        }

        return $this->successResponse(new OrderResource($order->load(['items', 'shippingAddress', 'histories'])), 'Order marked ready for collection.');
    }

    public function pickupCollected(Request $request, string $orderNumber, ReferralService $referrals): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $orderNumber, $referrals): Order {
            $order = Order::query()->where('order_number', $orderNumber)->lockForUpdate()->firstOrFail();
            abort_unless($order->delivery_method === 'self_collect', 422, 'This is not a Self Collect order.');
            if ($order->picked_up_at) {
                return $order;
            }
            abort_unless($order->pickup_ready_at && $order->status === OrderStatus::Packed, 422, 'Mark the order ready before confirming collection.');
            abort_unless($order->payment_method === 'cod' || $order->payment_status === PaymentStatus::Paid, 422, 'Payment has not been verified.');
            $order->update(['status' => OrderStatus::Delivered, 'picked_up_at' => now()]);
            $order->histories()->create(['status_type' => 'order', 'from_status' => OrderStatus::Packed->value, 'to_status' => OrderStatus::Delivered->value, 'note' => 'Collected by customer', 'changed_by' => $request->user()->id]);
            if ($order->payment_method === 'cod' && $order->payment_status === PaymentStatus::CodPending) {
                $order->update(['payment_status' => PaymentStatus::Paid]);
                DB::table('payment_attempts')->where('order_id', $order->id)->where('status', 'cod_pending')->update(['status' => 'succeeded', 'updated_at' => now()]);
                DB::table('payment_transactions')->where('order_id', $order->id)->where('provider_code', 'COD')->where('status', 'pending')->update(['status' => 'succeeded', 'updated_at' => now()]);
                $order->histories()->create(['status_type' => 'payment', 'from_status' => PaymentStatus::CodPending->value, 'to_status' => PaymentStatus::Paid->value, 'note' => 'Payment received at collection', 'changed_by' => $request->user()->id]);
                $referrals->awardForPaidOrder($order);
            }

            return $order->refresh();
        });

        return $this->successResponse(new OrderResource($order->load(['items', 'shippingAddress', 'histories'])), 'Customer collection confirmed.');
    }
}
