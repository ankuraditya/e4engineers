# Payment provider setup

Open **Admin → Payment Management**. Global online payments are disabled by default and all online gateways start disabled with no credentials.

For Razorpay enter `key_id`, `key_secret`, and optional `webhook_secret`. For PayU enter `merchant_key` and `merchant_salt`. For Cashfree enter `client_id` and `client_secret`. Save credentials, test the connection, enable the provider, then optionally make it the default. Configure test/sandbox first. Changing environment deletes the stored credentials and requires a new connection test.

Configure provider webhooks to these endpoints:

- `POST /api/v1/payments/webhooks/RAZORPAY`
- `POST /api/v1/payments/webhooks/CASHFREE`
- `POST /api/v1/payments/webhooks/PAYU`

PayU browser success and failure URLs use `POST /api/v1/payments/callbacks/payu`. Set `APP_URL` to the public backend origin and `FRONTEND_URL` to the React site origin before testing redirects.

Production checklist:

1. Use HTTPS for frontend, API, callbacks, and webhooks.
2. Enter live credentials only after selecting Live mode.
3. Test connection, enable one gateway, and run a small real transaction.
4. Confirm the webhook reaches the API and the order becomes paid only after server verification.
5. Keep the scheduler/cron worker running for attempt expiry and inventory release.
6. Restrict payment configuration, transaction viewing, and reconciliation permissions to trusted staff.

No demo or production credentials are committed to the repository. Live connection and settlement testing therefore remains an environment-specific deployment step.
