# Backend integration audit

## Phase 16 coupons and promotions

- Persistent carts reference a coupon by foreign key and never store an authoritative discount amount.
- `CouponService` is the only discount calculation and validation path used by cart reads, mutations, apply, remove, merge, and checkout display.
- React submits only a normalized coupon code; server book prices determine all eligible subtotals and discounts.
- Existing Book, Category, Engineering Discipline, User, Cart, and Inventory models are reused.
- Guest coupons remain provisional where customer usage history is required and are revalidated after authentication.
- Authenticated-cart coupons win merge conflicts; only one coupon can be attached.
- Existing cart mutations trigger revalidation and automatic invalid-coupon removal.
- Coupon application does not create `coupon_usages`, reserve inventory, deduct inventory, or create inventory movements.
- Admin actions use the existing Sanctum, admin-role, Gate, standard response, request validation, pagination, and soft-delete conventions.
- Cart and checkout render one shared backend summary. No JavaScript discount calculation exists.
- Shipping, orders, payments, invoices, shipment tracking, and refunds remain outside this phase.

## Phase 17 shipping addendum

- NimbusPost and Shiprocket sit behind one provider interface and manager.
- Credentials use encrypted database casts; raw secrets and tokens never enter API resources or React.
- Saved customer addresses are ownership-scoped; guest estimates accept only a postal code.
- Package attributes come from books and server settings. Client weight, dimensions, prices, discounts, and charges are ignored.
- Free-shipping eligibility uses the Phase 16 discounted subtotal.
- Quotes are short-lived UUID records and do not represent orders or shipments.
- Shipping checks create no inventory movements and perform no stock reservation or deduction.
# Phase 18 checkout integration

The frontend checkout, order success, My Orders, and Order Detail screens now use Laravel APIs. Guest carts remain identified by the opaque cart header through shipping and order placement. Server-side cart, catalogue price, inventory, coupon, address ownership, and shipping quote checks form the final order boundary. COD inventory deduction and coupon usage are atomic with order creation. Online payments, invoices, refunds, and live shipping bookings remain unimplemented by design.

# Phase 20 integration audit

Invoice issuance is integrated after confirmed COD checkout and after authoritative online payment success. It does not alter payment verification, inventory, coupon, shipping, or order state. Invoice PDFs use private storage and owner/token authorization. Settings, sequences, invoice headers, items, downloads, regeneration, voiding, admin UI and customer order surfaces are connected. GST/HSN calculation, credit notes, shipment booking, and refund accounting remain outside Phase 20.


## Phase 21 integration audit

Shipment booking now uses persisted orders, addresses, catalogue weights, Phase 17 providers and pickup locations. Provider calls are idempotent, timeout outcomes require reconciliation, webhook/poll events are deduplicated, documents remain private, and cancellation does not mutate financial, invoice, refund or inventory records. Frontend order tracking and the admin shipment workbench consume the new APIs.

## Phase 22 integration audit

Dynamic SMTP, strict email templates, order confirmation, account setup, payment success/failure, invoice-ready, shipment milestone mail, preferences, logs, deduplication and retry are integrated through domain events. SMS, WhatsApp and Firebase/push remain provider-free foundations.
