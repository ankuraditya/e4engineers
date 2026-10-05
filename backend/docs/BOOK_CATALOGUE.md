# Physical Book Catalogue

Phase 12 adds the physical product catalogue. `books` owns catalogue identity and presentation: stable slug and SKU, normalized nullable ISBN, discipline, reusable `categories` rows with `book`, `general`, or `all` context, publisher, ordered authors, cover and gallery media, physical format, and decimal INR pricing.

SKUs are generated once as `E4E-BOOK-####` when an administrator omits one. Slugs and SKUs do not change when a title changes. ISBN input has spaces and hyphens removed and must contain 10 or 13 characters. MRP and selling price use `decimal(12,2)`; selling price cannot exceed MRP. Savings and discount percentage are calculated in API resources.

Authors are independent of CMS contributors. `author_book` stores role and order. Covers reference the Phase 05 media library directly; `book_images` supplies an independently ordered gallery. SEO uses the existing polymorphic `seo_meta` architecture. Related books are published books sharing a discipline, category, or author, limited to four.

## Inventory and cart handoff

No stock quantity or stock state is stored in this module. Public responses contain `inventory.managed: false` and `PENDING_INVENTORY_INTEGRATION`. Phase 13 will own inventory and availability filters. The existing React cart remains local. Phase 15 must re-read `selling_price` from Laravel and must never trust a price posted by the browser.

## API

Public: `GET /api/v1/books`, `GET /api/v1/books/{slug}`, authors and publishers list/detail. The book list supports search, discipline, category, author, publisher, price range, featured, new arrival, safe sorting, and pagination.

Admin: CRUD under `/api/v1/admin/books`, `/authors`, and `/publishers`; book status, featured, new-arrival, gallery, and SEO endpoints. Book permissions are `books.*`, `authors.*`, and `publishers.*`, including `books.images.manage`.
