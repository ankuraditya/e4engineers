# Order architecture

Orders retain immutable commercial history in `orders`, `order_items`, and `order_addresses`. Product, author, cover, price, coupon, delivery address, shipping quote, and totals are snapshots and are never reconstructed from current catalogue records.

Order, payment, and shipping state are separate enums. A COD checkout starts as `confirmed`, `cod_pending`, and `not_created`. `OrderStatusService` permits explicit forward transitions and cancellation from pre-shipment states only. Admin cancellation restores each item's stock once and records `inventory_restored_at`, inventory movements, and status history in one transaction.

Authenticated account routes scope every query to `user_id`. Guest success access requires a 64-character random token stored as a SHA-256 verifier; the recoverable retry copy uses Laravel's encrypted cast. Order numbers are public references, not authorization credentials.

Admin order access is permission-gated and supports order/customer/contact/status/date filters. Shipment creation, AWB operations, invoices, online payment transactions, refunds, and returns are reserved for later phases.
