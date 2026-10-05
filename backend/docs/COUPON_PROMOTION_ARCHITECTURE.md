# Coupon and promotion architecture

Phase 16 supports one explicit coupon code per persistent cart. Codes are trimmed and normalized to uppercase. Laravel owns validity, eligibility, usage-limit checks, eligible subtotal, discount, and payable-before-shipping calculations. React submits only a code and displays the returned cart.

Supported discount types are `percentage` and `fixed`. Percentage values use the human convention (`10.00` means 10%) and may have a maximum cap. Fixed discounts and percentage results can never exceed the eligible subtotal. All calculations convert decimal price strings to integer paise, use half-up rounding to two decimal places, and return formatted decimal strings.

Scopes are exclusive: all books, selected books, selected book categories, or selected engineering disciplines. Only current published, non-deleted books participate. Minimum subtotal is evaluated against the eligible subtotal before discount. Shipping and tax are absent from this phase.

Generic coupons can be applied by guests. Per-customer limits are provisional for guests and revalidated after login. During cart merge, the authenticated cart coupon wins a conflict; otherwise the guest coupon transfers. The retained coupon is revalidated against the merged cart and authenticated customer.

Every cart read and mutation revalidates the attached coupon. Expired, inactive, deleted, over-limit, below-minimum, or no-longer-applicable coupons are detached immediately and returned as a `COUPON_REMOVED_*` warning. Applying another valid code replaces the prior coupon. Discounts are never cached.

Applying, removing, or abandoning a coupon does not create `coupon_usages`. The usage table is a Phase 18 foundation. Order creation must lock the coupon, recheck limits and customer usage, snapshot the coupon and discount onto the order, then create one uniquely referenced usage record in the same transaction.

## Customer API

- `POST /api/v1/cart/coupon` with `{ "code": "E4SAVE10" }`
- `DELETE /api/v1/cart/coupon`

Failures include a stable top-level `code`, such as `COUPON_NOT_FOUND`, `COUPON_EXPIRED`, `MINIMUM_SUBTOTAL_NOT_MET`, `NO_ELIGIBLE_ITEMS`, `USAGE_LIMIT_REACHED`, or `CUSTOMER_LIMIT_REACHED`.

## Admin API

- `GET|POST /api/v1/admin/coupons`
- `GET|PUT|PATCH|DELETE /api/v1/admin/coupons/{coupon}`
- `PATCH /api/v1/admin/coupons/{coupon}/status`

Filters include search, active state, discount type, scope, active validity, sorting by newest, and pagination.
