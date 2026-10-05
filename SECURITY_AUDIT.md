# Security audit

Status: incomplete; no production security sign-off. The local automated suite passes and includes customer order/address isolation, admin authorization, payment and shipping cases. Rich CMS, article, book, course, publication, and resource HTML is routed through `HtmlSanitizer` on relevant writes. A source search found no obvious interpolated user input in the sampled raw SQL expressions; this is not a full injection audit.

Phase 25 frontend banner links reject non-HTTP(S) schemes and protocol-relative URLs. Legal CMS content is rendered from the backend's sanitized write path. Continue reviewing every `dangerouslySetInnerHTML` use and imported legacy content before release.

Required before sign-off: enumerate every `/api/v1/admin/*` action with its permission; cross-user tests for orders, invoices, payments, downloads, support, and tracking; file upload MIME/size/path tests; direct public URL checks for private resources, invoices, labels, resumes, and attachments; webhook signature and replay tests; payment amount/currency authority; provider secret storage; shipping/inventory/coupon concurrency; production CORS, CSRF, cookie, HTTPS, security-header, and rate-limit review. A dependency and secret scan must run in the deployment environment without printing secret values into reports.

The current local `.env` has `APP_ENV=local`, `APP_DEBUG=true`, and SQLite. These settings are not a production configuration. No live credential was added by this audit.

A narrow source scan across application/config/route/database code found no filenames matching common live payment, AWS access-key, or private-key patterns. This is not a complete repository history or dependency secret scan and must not be treated as proof that no secret has ever been committed.

`npm audit --offline --audit-level=high` reported zero frontend advisories from the available local cache. `composer audit --locked` could not reach Packagist from this environment, so PHP dependency advisory status remains unverified.

Phase 25 local hardening: PayU return validation now compares the signed payment amount and order reference with the stored attempt. A signed success webhook without a numeric reported amount is rejected; Razorpay and Cashfree success webhooks also require a reported currency. Regression tests cover the PayU mismatch and missing webhook amount. The full local backend suite passed after these changes (170 tests, 845 assertions). This does not substitute for provider sandbox verification or a complete payment-flow audit.
