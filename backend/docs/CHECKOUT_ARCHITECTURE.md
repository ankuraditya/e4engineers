# Checkout architecture

Phase 18 uses a single public, one-page checkout endpoint. A guest supplies contact and delivery details; an authenticated customer may select an owned saved address. Registration and passwords are never required before placing an order.

`CheckoutService` resolves and locks the active cart, normalizes guest identity, creates a customer only when neither email nor mobile exists, validates address ownership, recalculates the cart, verifies the unexpired cart-bound shipping quote, creates immutable order snapshots, deducts inventory, records coupon usage, and converts the cart inside one database transaction.

The browser sends only identifiers and customer input. Subtotal, discount, shipping, stock, book prices, tax, and grand total are server-owned. COD is the only enabled method. Provider payment and shipment booking are outside Phase 18.

Every request includes an idempotency key. Its unique order constraint prevents duplicates and retries return the same order and encrypted-at-rest guest success token. A guest collision remains unauthenticated and the order remains safely unlinked until a later claim flow. A new guest receives a queued password-setup notification and no generated password is disclosed.

Post-commit work consists of the `OrderPlaced` event and queued email notifications. Transaction failures preserve the active cart and roll back the customer, order, coupon usage, and stock changes.
