# Phase 15 backend integration audit

- Book IDs sent by listing, detail, home catalogue, cart, and checkout now enter the same persistent API cart.
- Header count uses `summary.quantity_count`; cart and checkout consume the same server response.
- Client price, title, subtotal, stock, and identity fields are ignored on writes.
- Guest tokens identify only guest carts and are invalidated after authenticated merge.
- Cart item mutation queries include the current cart ID, preventing horizontal access.
- Published state, soft deletion, price, and inventory are revalidated on every read.
- Add/update/merge use transactions, row locks, and unique constraints.
- Cart code calls only `ensureAvailable`; it never reserves, deducts, or writes movements.
- Legacy `e4engineers-development-cart` contents are imported by book ID and quantity once, then removed. Invalid or unavailable entries are discarded by server validation.
- Coupons, shipping, orders, and payments remain outside Phase 15.

## Phase 16 addendum

- Cart and checkout now share the same coupon and server-calculated summary.
- Only the coupon code crosses from React on apply; discount inputs are ignored.
- Coupon restrictions use existing Book, Category, and Engineering Discipline records.
- Cart reads and mutations immediately revalidate coupon dates, status, restrictions, prices, minimum subtotal, and consumed usage.
- Coupon application creates neither a usage record nor an inventory movement.
- Shipping, orders, payments, and redemption remain deferred.
