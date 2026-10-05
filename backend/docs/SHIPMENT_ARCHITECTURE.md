# Shipment architecture

Phase 21 extends the Phase 17 shipping-provider layer into fulfilment. `ShipmentService` is the sole booking boundary and uses the existing provider manager for Shiprocket and NimbusPost. One order has at most one shipment. Provider calls occur after the local shipment is committed, and every booking uses an idempotency key and attempt record.

Eligible orders are confirmed COD orders with `cod_pending` payment or confirmed online orders with `paid` payment. Package, address, payment mode, COD amount, and pickup data come from trusted order and catalogue records. Browser-supplied prices, weights, or payment state are never used.

Booking timeouts become `unknown` and require reconciliation before retry. Explicit failures become `booking_failed`. Admin actions cover booking, retry, reconciliation, AWB, pickup, label, manifest, cancellation, and tracking refresh. Labels and manifests are stored on Laravel's private local disk and downloaded through authorized routes.

Automatic fulfilment settings default off. Tracking sync runs every five minutes and only polls active shipments whose configurable sync interval has elapsed.

## Provider boundary

Shiprocket mappings follow the official external API operations for adhoc order creation, AWB assignment, pickup generation, labels, manifests, cancellation, tracking, and webhooks. NimbusPost mappings are isolated in its adapter because endpoint collections can vary by account/API version; deployments can set provider base URLs without changing domain logic.

Provider credentials and webhook secrets are encrypted by `ShippingProvider`. Logs and API failures contain stable internal codes and sanitized messages.

## Safety rules

- Shipment cancellation does not restore inventory, alter invoices, refund payments, or recalculate order totals.
- Delivered and RTO progress cannot be regressed by older carrier events.
- Duplicate webhook events and tracking scans are ignored using provider event IDs and event hashes.
- Order shipping status is a summary; shipment status and its immutable timeline remain authoritative for fulfilment.
