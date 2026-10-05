<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Models\PaymentProvider;
use App\Models\PaymentWebhookEvent;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(private PaymentGatewayManager $gateways, private PaymentService $payments) {}

    public function __invoke(Request $r, string $provider): JsonResponse
    {
        $p = PaymentProvider::where('code', strtoupper($provider))->firstOrFail();
        $raw = $r->getContent();
        $v = $this->gateways->for($p)->verifyWebhook($p, $raw, $r->headers->all());
        abort_unless($v['valid'], 401);
        $payload = $v['payload'];
        $eventId = (string) ($r->header('x-razorpay-event-id') ?? data_get($payload, 'data.payment.cf_payment_id') ?? hash('sha256', $raw));
        $event = PaymentWebhookEvent::firstOrCreate(['provider_code' => $p->code, 'event_id' => $eventId], ['event_type' => $payload['event'] ?? data_get($payload, 'type'), 'payload_hash' => hash('sha256', $raw), 'status' => 'received', 'payload' => $payload]);
        if ($event->processed_at) {
            return response()->json(['success' => true]);
        }$providerOrder = (string) (data_get($payload, 'payload.payment.entity.order_id') ?? data_get($payload, 'data.order.order_id') ?? data_get($payload, 'txnid') ?? '');
        $attempt = PaymentAttempt::where('payment_provider_id', $p->id)->where(fn ($q) => $q->where('provider_order_id', $providerOrder)->orWhere('id', $providerOrder))->with('provider')->first();
        if ($attempt) {
            $status = strtoupper((string) (data_get($payload, 'payload.payment.entity.status') ?? data_get($payload, 'data.payment.payment_status') ?? data_get($payload, 'status')));
            $paymentId = (string) (data_get($payload, 'payload.payment.entity.id') ?? data_get($payload, 'data.payment.cf_payment_id') ?? data_get($payload, 'mihpayid') ?? '');
            $reportedAmount = data_get($payload, 'payload.payment.entity.amount') ?? data_get($payload, 'data.payment.payment_amount') ?? data_get($payload, 'amount');
            $reportedCurrency = data_get($payload, 'payload.payment.entity.currency') ?? data_get($payload, 'data.order.order_currency') ?? data_get($payload, 'currency');
            if (in_array($status, ['CAPTURED', 'SUCCESS', 'PAID'], true)) {
                abort_unless(is_numeric($reportedAmount), 422, 'Payment amount is required.');
                if ($p->code !== 'PAYU') {
                    abort_unless(filled($reportedCurrency), 422, 'Payment currency is required.');
                }
            }
            if ($reportedAmount !== null) {
                $normalizedAmount = $p->code === 'RAZORPAY' ? ((float) $reportedAmount / 100) : (float) $reportedAmount;
                abort_unless(abs($normalizedAmount - (float) $attempt->amount) < 0.001, 422, 'Payment amount mismatch.');
            }
            abort_if($reportedCurrency !== null && strtoupper((string) $reportedCurrency) !== $attempt->currency, 422, 'Payment currency mismatch.');
            in_array($status, ['CAPTURED', 'SUCCESS', 'PAID'], true) ? $this->payments->succeed($attempt, $paymentId, $payload) : ($status === 'FAILED' ? $this->payments->fail($attempt, $payload) : null);
        }$event->update(['status' => 'processed', 'processed_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function payuCallback(Request $request): RedirectResponse
    {
        $this($request, 'PAYU');
        $attempt = PaymentAttempt::with('order')->findOrFail($request->input('txnid'));
        $target = rtrim(config('e4engineers.frontend_url'), '/').'/order-success/'.rawurlencode($attempt->order->order_number);

        return redirect()->away($target);
    }
}
