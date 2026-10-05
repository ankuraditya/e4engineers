# Shipping architecture

Phase 17 adds a provider-neutral shipping layer for NimbusPost and Shiprocket. React calls only Laravel. `ShippingService` derives the cart, current discounted subtotal, package weight, default dimensions, origin, destination, and payment mode from trusted records. `ShippingProviderManager` selects enabled providers, applies technical-failure fallback, and returns normalized quotes.

Provider configuration uses Laravel's `encrypted:array` cast. API responses return non-secret values, configuration flags, and blank secret inputs. Temporary bearer tokens use provider-and-account-specific cache keys and are invalidated when credentials change. Logs and audit events never contain credentials, bearer tokens, or complete provider responses.

Shiprocket authentication follows its current API-user email/password bearer-token flow and serviceability endpoint. NimbusPost uses API-user email/password and its courier serviceability/freight flow; its adapter keeps provider field mapping isolated because NimbusPost documentation is distributed through its official Postman collection. The reviewed Shiprocket documentation is [apidocs.shiprocket.in](https://apidocs.shiprocket.in/). Provider calls use a 12-second timeout and normalized safe errors.

Shipping modes are `live_provider`, `flat_rate`, `free`, and `hybrid`. Free-shipping thresholds use the authoritative discounted merchandise subtotal after coupons. Flat fallback occurs only when explicitly enabled. Package weight sums book weights by quantity and falls back to the configured default. Phase 17 uses configurable default carton dimensions because combining individual dimensions does not reliably describe a packed carton.

Quotes expire after 15 minutes and carry opaque UUIDs. They are estimates, not shipments or orders. Phase 18 must verify ownership, expiry, request/cart hash, price, coupon, inventory, address, and the selected quote before snapshotting shipping onto an order.

Rate checks never reserve or deduct stock and never create inventory movements. Shipment creation, AWB assignment, labels, manifests, pickup booking, tracking, cancellation, and returns remain adapter-ready but cannot execute without a local order/shipment.

## Phase 21 fulfilment extension

The Phase 17 provider contract now also covers shipment creation, AWB assignment, pickup, labels, manifests, cancellation and tracking. Provider-specific payloads remain inside the Shiprocket and NimbusPost adapters; `ShipmentService` owns eligibility, idempotency, persistence, state precedence and order-summary synchronization. See `SHIPMENT_ARCHITECTURE.md` and `TRACKING_ARCHITECTURE.md`.
