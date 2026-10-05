# Notification architecture

`NotificationManager` is the single boundary between business events and delivery channels. Orders, payments, invoices, and shipments dispatch domain events. The subscriber builds an approved context and requests one normalized notification type. A unique deduplication key creates one delivery log and one job per milestone.

Email is implemented through database-managed SMTP. SMS, WhatsApp, and push exist only as channel enum values for future adapters. Business modules never call those providers.

SMTP credentials use Laravel's encrypted cast. Runtime configuration is applied immediately to the isolated `dynamic` mailer, which is purged before reuse. Disabled or incomplete settings never silently fall back to another mailer. Admin responses expose only `password_configured`.

Templates use a strict `{{variable}}` renderer. Each template owns an allowlist. Unknown variables, multiline subjects, scripts, iframes, event handlers, and unsafe URL schemes are rejected or removed. Database content is never evaluated as PHP or Blade.

Logs use `queued`, `sent`, `failed`, and `skipped`. Sent means accepted by SMTP, not inbox delivery. Context is encrypted and complete rendered bodies are not stored. Queued jobs retry with 60, 300, and 900 second backoff. Manual retry reuses the same log.

Account security messages ignore optional preferences. Order, payment, and shipping updates respect their category preferences. Marketing remains opt-in. Mail failure occurs after the business transaction and cannot roll back orders, payments, invoices, or shipments.
