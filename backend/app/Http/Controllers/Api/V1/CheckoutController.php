<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Notifications\SetAccountPasswordNotification;
use App\Services\CheckoutService;
use App\Services\Payment\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CheckoutService $checkout, private readonly PaymentService $payments) {}

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $result = $this->checkout->place($request, $request->validated());
        if (! $result['replayed']) {
            OrderPlaced::dispatch($result['order']);
        }
        $user = $result['user'];
        if ($result['account_created'] && $user) {
            $token = Password::broker()->createToken($user);
            $user->notify(new SetAccountPasswordNotification($token));
            Auth::guard('web')->login($user);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
        }

        $message = $result['account_created']
            ? 'Order placed. We created your account and emailed a secure password setup link.'
            : ($result['account_collision'] ? 'Order placed. Sign in later to securely claim it from your existing account.' : 'Order placed successfully.');

        $payment = null;
        if (! in_array($result['order']->payment_method, ['cod', 'scanpay'], true)) {
            $attempt = $this->payments->initiate($result['order'], $request->validated('idempotency_key').':payment');
            $payment = ['attempt_id' => $attempt->id, 'status' => $attempt->status, 'provider' => $attempt->provider->code, 'client_action' => $attempt->client_action, 'expires_at' => $attempt->expires_at];
        }

        return $this->successResponse([
            'order' => new OrderResource($result['order']),
            'order_access_token' => $result['access_token'],
            'account_created' => $result['account_created'],
            'existing_account_detected' => $result['account_collision'],
            'payment' => $payment,
        ], $message, 201);
    }
}
