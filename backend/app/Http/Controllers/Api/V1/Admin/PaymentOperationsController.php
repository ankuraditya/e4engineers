<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentOperationsController extends Controller
{
    use ApiResponse;

    public function attempts(Request $request): JsonResponse
    {
        $items = PaymentAttempt::with(['provider:id,code,name', 'order:id,order_number'])->latest()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->paginate(25);

        return $this->successResponse($items->items(), meta: ['pagination' => ['total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function transactions(): JsonResponse
    {
        $items = PaymentTransaction::with('order:id,order_number')->latest()->paginate(25);

        return $this->successResponse($items->items(), meta: ['pagination' => ['total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function reconcile(PaymentAttempt $attempt, PaymentService $payments): JsonResponse
    {
        if (in_array($attempt->status, ['pending', 'initiating'], true) && $attempt->expires_at->isPast()) {
            $payments->fail($attempt, ['code' => 'EXPIRED_DURING_RECONCILIATION']);
            $attempt->update(['status' => 'expired']);
        }

        return $this->successResponse($attempt->refresh(), 'Payment attempt reconciled.');
    }

    public function proof(PaymentAttempt $attempt): StreamedResponse
    {
        abort_unless($attempt->provider?->code === 'SCANPAY' && $attempt->proof_path && Storage::disk('private')->exists($attempt->proof_path), 404);

        return Storage::disk('private')->response($attempt->proof_path, 'payment-proof-'.$attempt->order_id, ['Content-Type' => $attempt->proof_mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function review(Request $request, PaymentAttempt $attempt, PaymentService $payments): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:500']]);
        abort_unless($attempt->provider?->code === 'SCANPAY' && $attempt->status === 'pending_review' && $attempt->proof_path, 422, 'This scan payment is not awaiting review.');
        abort_unless($attempt->order?->payment_status === PaymentStatus::Pending, 422, 'This order is no longer awaiting payment.');
        if ($data['decision'] === 'approve') {
            $payments->succeed($attempt, 'SCANPAY-'.$attempt->id, ['reviewed_by' => $request->user()->id, 'reference' => $attempt->proof_reference]);
        } else {
            $payments->fail($attempt, ['code' => 'MANUAL_PROOF_REJECTED', 'reviewed_by' => $request->user()->id]);
        }
        $attempt->update(['proof_reviewed_at' => now(), 'proof_reviewed_by' => $request->user()->id, 'proof_review_note' => $data['note'] ?? null]);

        return $this->successResponse($attempt->refresh(), 'Scan payment review recorded.');
    }
}
