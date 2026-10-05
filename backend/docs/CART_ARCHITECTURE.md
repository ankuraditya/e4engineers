# Cart architecture

Phase 15 replaces browser-owned cart contents with persistent Laravel carts. A cart belongs either to an authenticated customer or to a cryptographically random 64-character guest token. The browser stores only that opaque token under `e4engineers-guest-cart-token`; it never stores cart lines, prices, totals, or stock. The token is sent in `X-Guest-Cart-Token`, is rotated away when merged, and must be protected like a bearer credential. HTTPS is mandatory in staging and production.

`carts.active_user_id` is unique and populated only for an active customer cart, enforcing one active cart per customer. `cart_items(cart_id, book_id)` is unique. Mutations run in transactions and lock the cart and relevant line. Guest carts are isolated by their opaque token; customer carts are resolved only from the authenticated session. Client-supplied cart item IDs are always scoped to the resolved cart.

The current `books.selling_price` is the sole price authority. `unit_price_snapshot` detects price changes and never authorizes a total. Monetary arithmetic is performed in integer paise and formatted as decimal strings. Every cart read revalidates publication state, soft deletion, current price, and available inventory. Structured issues disable checkout without silently removing or reducing lines.

Adding and updating call `InventoryService::ensureAvailable()`. Cart operations never reserve or deduct inventory and never create inventory movements. Merge combines equal books and caps the result at current availability, returns `QUANTITY_CAPPED` warnings, then marks the guest cart converted and invalidates its token. Inventory must be locked and revalidated again when Phase 18 creates an order.

No cart response is cached because price and availability must be fresh. Phase 16 may calculate coupons from the returned authoritative subtotal without changing line prices. Phase 17 may add shipping quotes. Phase 18 may consume this cart only after a final transactional price and inventory revalidation.

## API

- `GET /api/v1/cart`
- `POST /api/v1/cart/items` with `book_id`, `quantity`
- `PATCH /api/v1/cart/items/{cartItem}` with `quantity`
- `DELETE /api/v1/cart/items/{cartItem}`
- `DELETE /api/v1/cart`
- `POST /api/v1/cart/merge` (authenticated)

Responses contain current item identity/display data, current MRP and selling price, line subtotal, stock status without an exact public count, price-change data, summary counts, authoritative subtotal, issues, warnings, and `checkout_allowed`.
