<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\DigitalEntitlement;
use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\ReferralService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ApiResponse;

    public function dashboard(Request $request): JsonResponse
    {
        $orders = Order::query()->where('user_id', $request->user()->id);
        $user = $request->user();
        $eligible = app(ReferralService::class)->eligible($user);
        $referralCode = $eligible ? app(ReferralService::class)->codeFor($user) : null;

        return $this->successResponse([
            'total_orders' => (clone $orders)->count(),
            'orders_in_progress' => (clone $orders)->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])->count(),
            'delivered_orders' => (clone $orders)->where('status', OrderStatus::Delivered->value)->count(),
            'digital_resources' => DigitalEntitlement::query()->forUser($request->user())->valid()->count(),
            'recent_orders' => OrderResource::collection((clone $orders)->with('items')->latest('placed_at')->limit(3)->get())->resolve(),
            'referral' => [
                'eligible' => $eligible,
                'code' => $referralCode,
                'reward_amount_rupees' => (int) (WebsiteSetting::query()->where('key', 'referral_reward_rupees')->value('value') ?? 100),
                'minimum_withdrawal_rupees' => (int) (WebsiteSetting::query()->where('key', 'referral_min_withdrawal_rupees')->value('value') ?? 100),
                'registered' => User::query()->where('referred_by_user_id', $user->id)->count(),
                'rewarded' => DB::table('referral_rewards')->where('referrer_user_id', $user->id)->whereNull('revoked_at')->count(),
                'store_credit_rupees' => number_format(max(0, DB::table('store_credit_entries')->where('user_id', $user->id)->sum('amount_paise')) / 100, 2, '.', ''),
                'wallet_balance_rupees' => number_format(max(0, DB::table('referral_wallet_entries')->where('user_id', $user->id)->sum('amount_paise')) / 100, 2, '.', ''),
                'withdrawals' => DB::table('referral_withdrawals')->where('user_id', $user->id)->latest()->limit(20)->get(['id', 'amount_paise', 'status', 'created_at', 'reviewed_at']),
                'rewards' => DB::table('referral_rewards')->join('users', 'users.id', '=', 'referral_rewards.referred_user_id')->leftJoin('referral_wallet_entries as wallet', 'wallet.referral_reward_id', '=', 'referral_rewards.id')->where('referrer_user_id', $user->id)->whereNull('referral_rewards.revoked_at')->orderByDesc('credited_at')->limit(10)->get(['users.name', 'referral_rewards.amount_paise', 'referral_rewards.credited_at', 'wallet.id as wallet_entry_id']),
            ],
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
