# Payment architecture

Phase 19 adds database-managed Razorpay, PayU, Cashfree, and cash-on-delivery payments.

## Payment lifecycle

1. Checkout creates an immutable order snapshot. Online orders enter `payment_pending` and reserve inventory; COD orders are confirmed immediately.
2. `PaymentService` creates one idempotent payment attempt and the selected adapter creates the provider order or signed form.
3. The browser opens the provider-hosted checkout. Card, CVV, OTP, and banking credentials never pass through or persist in this application.
4. A signed return or webhook is verified with the provider secret. The server also checks the attempt amount and currency against the order.
5. Success finalizes reserved inventory once, writes a transaction, confirms the order, and dispatches `PaymentSucceeded`. Failure or expiry releases the reservation once and dispatches `PaymentFailed`.

Webhook events have a provider/event unique key, payment attempts have an idempotency key, and success/failure paths lock the order and attempt. These controls make duplicate callbacks safe. Late failure events cannot reverse a paid order.

Credentials use Laravel's encrypted array cast and are hidden from serialization. Switching test/live environment clears credentials and disables the provider so credentials cannot cross environments. Public and admin responses expose only masked configuration metadata.

The scheduler runs `payments:expire-attempts` every five minutes. Admin users with the reconciliation permission may reconcile an individual stale attempt. `payment_refunds` provides the persistence and idempotency foundation for the later refund workflow; Phase 19 does not execute refunds.

## Operational states

- Attempt: `initiating`, `pending`, `succeeded`, `failed`, `expired`, or `cod_pending`.
- Transaction: payment/refund type with provider reference and sanitized response metadata.
- Order: online orders remain payment-pending until server verification; COD remains `cod_pending` until collected.

Do not manually mark an order paid. Reconcile or replay the signed provider webhook instead.
