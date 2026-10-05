# Inventory architecture

`book_inventory` is the single authoritative stock record for each physical book. Available quantity is calculated as `stock_quantity - reserved_quantity`; it is not persisted. The invariant is `0 <= reserved_quantity <= stock_quantity`. Public status is derived as `OUT_OF_STOCK` at zero available, `LOW_STOCK` at 1 through the configured threshold, and `IN_STOCK` above it. Exact quantities are admin-only.

Every stock change runs through `InventoryService` inside a transaction and locks the inventory row with `lockForUpdate()`. Signed movement quantities record the delta: positive increases and negative decreases. The movement stores before/after values, reason, optional notes/reference, and authenticated actor. Movements cannot be edited or deleted through the API; errors require a correcting adjustment.

New books receive zero-stock inventory. The idempotent `php artisan e4engineers:inventory-initialize` command backfills missing records without changing existing stock. `InventoryDemoSeeder` is intended only for development/staging.

Public APIs expose status booleans without exact counts. Any inventory mutation increments the Book cache version. Phase 15 Cart must call `ensureAvailable()` and re-read stock. Phase 18 Checkout/Orders must lock inventory rows, revalidate quantities, reserve or deduct in the same transaction, and use movement references for sale, cancellation, and return restoration. No reservation endpoint is currently public.
