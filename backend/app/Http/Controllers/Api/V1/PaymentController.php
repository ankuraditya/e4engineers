<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use App\Models\PaymentSetting;
use App\Services\Payment\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentService $payments) {}

    public function methods(): JsonResponse
    {
        $s = PaymentSetting::current();
        $online = $s->online_payments_enabled ? PaymentProvider::where('type', 'online')->where('is_enabled', true)->where('connection_status', 'connected')->orderBy('sort_order')->get(['code', 'name', 'is_default']) : collect();

        $cod = PaymentProvider::where('code', 'COD')->first();
        $scan = PaymentProvider::where('code', 'SCANPAY')->where('is_enabled', true)->first();
        $scanConfiguration = $scan?->configuration ?? [];
        $scanEnabled = $scan && filled($scanConfiguration['qr_path'] ?? null) && Storage::disk('private')->exists($scanConfiguration['qr_path']);

        return $this->successResponse(['cod' => ['enabled' => $s->cod_enabled && ($cod?->is_enabled ?? true), 'charge' => $s->cod_charge], 'scanpay' => ['enabled' => (bool) $scanEnabled, 'upi_id' => $scanEnabled ? $scanConfiguration['upi_id'] : null, 'payee_name' => $scanEnabled ? $scanConfiguration['payee_name'] : null, 'qr_url' => $scanEnabled ? url('/api/v1/payments/scan-code') : null], 'online' => $online]);
    }

    public function scanCode(): StreamedResponse
    {
        $provider = PaymentProvider::where('code', 'SCANPAY')->where('is_enabled', true)->firstOrFail();
        $path = $provider->configuration['qr_path'] ?? null;
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response($path, 'scan-and-pay-qr', ['Content-Type' => Storage::disk('private')->mimeType($path), 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store']);
    }

    public function submitProof(Request $request, string $number): JsonResponse
    {
        $data = $request->validate([
            'screenshot' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        $order = $this->owned($request, $number);
        abort_unless($order->payment_method === 'scanpay', 404);
        $attempt = $order->paymentAttempts()->whereHas('provider', fn ($query) => $query->where('code', 'SCANPAY'))->latest()->firstOrFail();
        abort_unless(in_array($attempt->status, ['pending', 'pending_review'], true) && $attempt->expires_at->isFuture(), 422, 'This payment is no longer accepting screenshots.');
        $path = $request->file('screenshot')->store('payments/proofs', 'private');
        abort_unless($path, 500, 'Screenshot upload failed.');
        $oldPath = $attempt->proof_path;
        try {
            DB::transaction(function () use ($attempt, $path, $data, $request): void {
                $locked = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($locked->status, ['pending', 'pending_review'], true), 422);
                $locked->update(['proof_path' => $path, 'proof_mime' => $request->file('screenshot')->getMimeType(), 'proof_reference' => $data['reference'] ?? null, 'proof_uploaded_at' => now(), 'status' => 'pending_review']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('private')->delete($oldPath);
        }

        return $this->successResponse(['status' => 'pending_review', 'proof_uploaded_at' => now()], 'Screenshot received. Payment will be verified by the team.');
    }

    public function initiate(Request $r, string $number): JsonResponse
    {
        $d = $r->validate(['provider' => 'required|in:RAZORPAY,PAYU,CASHFREE', 'idempotency_key' => 'required|string|max:100']);
        $o = $this->owned($r, $number);
        $a = $this->payments->initiate($o, $d['idempotency_key'], $d['provider']);

        return $this->successResponse($this->attempt($a), status: 201);
    }

    public function verify(Request $r, string $number, string $attempt): JsonResponse
    {
        $o = $this->owned($r, $number);
        $a = PaymentAttempt::where('order_id', $o->id)->whereKey($attempt)->with('provider')->firstOrFail();

        return $this->successResponse($this->attempt($this->payments->verify($a, $r->all())));
    }

    public function status(Request $r, string $number): JsonResponse
    {
        $o = $this->owned($r, $number);

        return $this->successResponse(['order_number' => $o->order_number, 'order_status' => $o->status->value, 'payment_status' => $o->payment_status->value, 'attempt' => $o->paymentAttempts()->latest()->first()?->only(['id', 'status', 'expires_at'])]);
    }

    public function retry(Request $r, string $number): JsonResponse
    {
        return $this->initiate($r, $number);
    }

    private function owned(Request $r, string $n): Order
    {
        $o = Order::where('order_number', $n)->firstOrFail();
        $token = (string) $r->header('X-Order-Access-Token');
        abort_unless(($r->user() && $o->user_id === $r->user()->id) || ($token !== '' && hash_equals((string) $o->guest_access_token_hash, hash('sha256', $token))), 404);

        return $o;
    }

    private function attempt(PaymentAttempt $a): array
    {
        return ['id' => $a->id, 'status' => $a->status, 'provider' => $a->provider->code, 'environment' => $a->environment, 'client_action' => $a->client_action, 'expires_at' => $a->expires_at];
    }
}
