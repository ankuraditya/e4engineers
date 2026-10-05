<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways, private readonly InventoryService $inventory) {}

    public function initiate(Order $order, string $idempotencyKey, ?string $providerCode = null): PaymentAttempt
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages(['order' => ['This order is already paid.']]);
        }
        if ($existing = PaymentAttempt::where('idempotency_key', $idempotencyKey)->first()) {
            abort_unless($existing->order_id === $order->id, 409);

            return $existing;
        }
        $code = strtoupper($providerCode ?: $order->payment_method);
        $provider = PaymentProvider::where('code', $code)->where('type', 'online')->where('is_enabled', true)->firstOrFail();
        if ($provider->connection_status !== 'connected') {
            throw ValidationException::withMessages(['provider' => ['Payment gateway is not connected.']]);
        }
        if ($order->inventory_released_at) {
            DB::transaction(function () use ($order): void {
                foreach ($order->items()->with('book')->get() as $item) {
                    $this->inventory->reserve($item->book, $item->quantity, 'order', $order->order_number);
                } $order->update(['inventory_reserved_at' => now(), 'inventory_released_at' => null]);
            });
        }
        $attempt = PaymentAttempt::create(['id' => (string) Str::uuid(), 'order_id' => $order->id, 'payment_provider_id' => $provider->id, 'environment' => $provider->environment, 'status' => 'initiating', 'amount' => $order->grand_total, 'currency' => $order->currency, 'idempotency_key' => $idempotencyKey, 'expires_at' => now()->addMinutes(PaymentSetting::current()->attempt_expiry_minutes)]);
        try {
            $result = $this->gateways->for($provider)->initiate($provider, $attempt->load('order'), ['name' => $order->shippingAddress->name, 'email' => $order->shippingAddress->email, 'mobile' => $order->shippingAddress->mobile]);
            $attempt->update(['status' => 'pending', 'provider_order_id' => $result['provider_order_id'], 'client_action' => $result['action']]);
        } catch (\Throwable $e) {
            $this->fail($attempt, ['code' => 'INITIATION_FAILED', 'message' => 'Gateway initiation failed.']);
            throw $e;
        }

        return $attempt->refresh();
    }

    public function verify(PaymentAttempt $attempt, array $payload): PaymentAttempt
    {
        $result = $this->gateways->for($attempt->provider)->verifyReturn($attempt->provider, $attempt, $payload);
        if (! $result['valid']) {
            throw ValidationException::withMessages(['signature' => ['Invalid payment signature.']]);
        }

        return ($result['status'] ?? '') === 'success' ? $this->succeed($attempt, (string) ($result['payment_id'] ?? ''), $payload) : $this->fail($attempt, $payload);
    }

    public function succeed(PaymentAttempt $attempt, string $providerPaymentId, array $payload): PaymentAttempt
    {
        return DB::transaction(function () use ($attempt, $providerPaymentId, $payload): PaymentAttempt {
            $attempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->with(['order.items.book'])->firstOrFail();
            $order = Order::whereKey($attempt->order_id)->lockForUpdate()->firstOrFail();
            if ($order->payment_status === PaymentStatus::Paid) {
                return $attempt;
            }
            if ((string) $attempt->amount !== (string) $order->grand_total || $attempt->currency !== $order->currency) {
                throw ValidationException::withMessages(['amount' => ['Payment amount or currency mismatch.']]);
            }
            if (! $order->inventory_finalized_at) {
                foreach ($attempt->order->items as $item) {
                    $this->inventory->finalizeReservation($item->book, $item->quantity, $order->order_number);
                }
            }
            $attempt->update(['status' => 'succeeded', 'provider_payment_id' => $providerPaymentId]);
            PaymentTransaction::firstOrCreate(['payment_attempt_id' => $attempt->id, 'type' => 'payment', 'provider_transaction_id' => $providerPaymentId], ['order_id' => $order->id, 'provider_code' => $attempt->provider->code, 'status' => 'succeeded', 'amount' => $attempt->amount, 'currency' => $attempt->currency, 'payload' => $this->sanitize($payload)]);
            $order->update(['payment_status' => PaymentStatus::Paid, 'status' => OrderStatus::Confirmed, 'inventory_finalized_at' => now()]);
            $order->histories()->create(['status_type' => 'payment', 'from_status' => PaymentStatus::Pending->value, 'to_status' => PaymentStatus::Paid->value, 'note' => 'Payment verified server-side']);
            $fresh = $attempt->refresh();
            DB::afterCommit(function () use ($fresh): void {
                PaymentSucceeded::dispatch($fresh);
                app(InvoiceService::class)->issueForOrder($fresh->order);
            });

            return $fresh;
        }, 3);
    }

    public function fail(PaymentAttempt $attempt, array $payload = []): PaymentAttempt
    {
        return DB::transaction(function () use ($attempt, $payload): PaymentAttempt {
            $attempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->with(['order.items.book'])->firstOrFail();
            $order = Order::whereKey($attempt->order_id)->lockForUpdate()->firstOrFail();
            if ($order->payment_status === PaymentStatus::Paid) {
                return $attempt;
            }
            $attempt->update(['status' => 'failed', 'failure' => $this->sanitize($payload)]);
            if (! $order->inventory_released_at && ! $order->inventory_finalized_at) {
                foreach ($attempt->order->items as $item) {
                    $this->inventory->releaseReservation($item->book, $item->quantity, $order->order_number);
                } $order->update(['payment_status' => PaymentStatus::Failed, 'inventory_released_at' => now()]);
            }
            $fresh = $attempt->refresh();
            DB::afterCommit(fn () => PaymentFailed::dispatch($fresh));

            return $fresh;
        });
    }

    private function sanitize(array $payload): array
    {
        foreach (['card', 'cvv', 'otp', 'key_secret', 'merchant_salt', 'client_secret'] as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }
}
