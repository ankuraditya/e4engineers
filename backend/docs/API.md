# E4ENGINEERS API

Local base URL: `http://localhost:8000/api/v1`

All responses use the Phase 01 envelope. Validation failures return HTTP 422 with field-keyed `errors`. Auth uses first-party Sanctum session cookies; call `GET /sanctum/csrf-cookie` before state-changing requests and send credentials.

## Health

### `GET /api/v1/health`

Public. Returns API version, environment, and safe database status.

## Customer authentication

### `POST /api/v1/auth/register`

Public; limited to 5 requests per minute per IP.

Fields: `name`, `email`, `mobile`, `password`, `password_confirmation`.

Returns HTTP 201 and `data.user`. Email is normalized to lowercase and mobile is stored as a 10-digit Indian number. The session is authenticated after registration.

### `POST /api/v1/auth/login`

Public; limited to 5 attempts per minute per login/IP pair.

```json
{"login":"customer@example.com","password":"password123","remember":false}
```

`login` accepts email or mobile. Success returns HTTP 200 and `data.user`; invalid credentials or disabled accounts return the same generic HTTP 401 response.

### `GET /api/v1/auth/me`

Requires an authenticated Sanctum session. Returns safe customer identity fields. Guests receive HTTP 401.

### `POST /api/v1/auth/logout`

Requires an authenticated Sanctum session and CSRF protection. Invalidates the current session and rotates its CSRF token.

### `POST /api/v1/auth/forgot-password`

Public; limited to 3 requests per minute per email/IP pair.

Field: `email`. Always returns the same safe success message for valid email input, whether or not the account exists. Development reset mail uses the configured log/array mailer.

### `POST /api/v1/auth/reset-password`

Public; limited to 3 requests per minute per email/IP pair.

Fields: `email`, `token`, `password`, `password_confirmation`. A successful reset invalidates existing database sessions and returns HTTP 200. Invalid or expired tokens return HTTP 422.

## Safe customer resource

Returned fields are `id`, `name`, `email`, `mobile`, verification timestamps, `status`, `last_login_at`, and `created_at`. Password hashes, remember tokens, reset tokens, and internal secrets are never returned.

## Administrative authentication

All protected admin routes require a Sanctum session and an administrative role. Permission checks are enforced by Laravel on the API. Customer-only accounts receive HTTP 403.

### `POST /api/v1/admin/auth/login`

Public; limited to 5 attempts per minute per email/IP pair. Fields: `email`, `password`. Only active users with an administrative role may authenticate. Every failure returns the same HTTP 401 message.

### `GET /api/v1/admin/auth/me`

Returns safe administrator identity, roles, effective permissions, status, and login timestamp.

### `POST /api/v1/admin/auth/logout`

Invalidates the current administrative session and rotates its CSRF token.

### `GET /api/v1/admin/dashboard`

Requires `admin.dashboard.access`. This is an authorization test endpoint; analytics are not implemented.

## Administrative users

- `GET /api/v1/admin/users` — permission: `admin-users.view`
- `POST /api/v1/admin/users` — permission: `admin-users.create`; fields: `name`, `email`, optional `mobile`, `password`, `password_confirmation`, `roles`
- `GET /api/v1/admin/users/{user}` — permission: `admin-users.view`
- `PUT|PATCH /api/v1/admin/users/{user}` — permission: `admin-users.update`; safe identity fields and optional `roles`
- `PATCH /api/v1/admin/users/{user}/status` — permission: `admin-users.activate` or `admin-users.suspend`; field: `status`

Passwords are never returned. General updates do not accept passwords. Only a Super Admin may assign or remove the `super-admin` role. The final active Super Admin and self-suspension are protected.

## Roles and permissions

- `GET /api/v1/admin/roles` — permission: `roles.view`
- `POST /api/v1/admin/roles` — permission: `roles.create`
- `GET /api/v1/admin/roles/{role}` — permission: `roles.view`
- `PATCH /api/v1/admin/roles/{role}` — permission: `roles.update`; protected roles cannot be renamed
- `PUT /api/v1/admin/roles/{role}/permissions` — permission: `permissions.assign`; protected roles cannot be synchronized through the API
- `GET /api/v1/admin/permissions` — permission: `permissions.view`

Permission names use `resource.action`. The controlled permission catalogue is seeded; arbitrary permission creation is not exposed.

## Engineering master data and taxonomy

Public endpoints return active records in `sort_order`, then `name`, order:

- `GET /api/v1/engineering-disciplines`
- `GET /api/v1/engineering-disciplines/{slug}`
- `GET /api/v1/categories` — optional `type`: `general`, `article`, `book`, `resource`, or `publication`
- `GET /api/v1/topics` — optional `discipline` slug
- `GET /api/v1/tags`
- `GET /api/v1/course-levels`
- `GET /api/v1/resource-types`
- `GET /api/v1/publication-types`

Authenticated admin endpoints are available at `/api/v1/admin/{master}` for each collection above:

- `GET /{master}` — requires `{master}.view`; optional `status=active|inactive`
- `POST /{master}` — requires `{master}.create`
- `GET /{master}/{id}` — requires `{master}.view`
- `PUT|PATCH /{master}/{id}` — requires `{master}.update`
- `PATCH /{master}/{id}/status` — requires `{master}.update`; body: `{"is_active":false}`
- `PATCH /{master}/reorder` — requires `{master}.reorder`; body: `{"items":[{"id":1,"sort_order":2}]}`. Tags do not expose reorder.

Create accepts `name`, optional `slug`, and fields appropriate to the master. Categories accept `context`; topics accept `engineering_discipline_id` and `parent_id`; disciplines accept `short_name`, `icon`, and `image`. Ordered masters accept `sort_order`. If omitted, a slug is generated from the name. Updating a name does not change an existing slug unless a new unique slug is explicitly supplied. Records use active/inactive status; hard-delete endpoints are intentionally absent.

Public discipline, course-level, resource-type, and publication-type lists are cached for one hour. Admin mutations invalidate the relevant cache immediately.

## Future business-listing convention

Business content APIs remain planned: `search`, `discipline`, allow-listed `sort`, `page`, and `per_page` (default 12, maximum 100).

## CMS public APIs

- `GET /api/v1/pages/{slug}` — published page with active ordered sections and SEO.
- `GET /api/v1/settings/public` — allowlisted frontend-safe settings only.
- `GET /api/v1/social-links` — active links in configured order.
- `GET /api/v1/banners?placement=homepage-hero` — active banners inside their optional date window.

Draft and archived pages return HTTP 404. Public responses exclude editor IDs and internal settings.

## CMS administration APIs

Pages require the matching `pages.*` permission:

- `GET|POST /api/v1/admin/pages`
- `GET|PUT|PATCH|DELETE /api/v1/admin/pages/{page}`
- `PATCH /api/v1/admin/pages/{page}/status` with `draft`, `published`, or `archived`
- `GET|POST /api/v1/admin/pages/{page}/sections`
- `PUT|PATCH|DELETE /api/v1/admin/page-sections/{section}`
- `PATCH /api/v1/admin/pages/{page}/sections/reorder`
- `GET|PUT /api/v1/admin/pages/{page}/seo`

System pages cannot be deleted and retain their stable slug. Rich content is sanitized before storage.

Other protected modules:

- `GET|PUT /api/v1/admin/settings` — `settings.view|update`; body uses `{"settings":[{"key":"site_name","value":"E4ENGINEERS"}]}` and rejects unknown keys.
- `/api/v1/admin/social-links` — list/create/update/delete, status, and reorder using `social-links.*`.
- `/api/v1/admin/banners` — list/create/view/update/delete, status, and reorder using `banners.*`.
- `/api/v1/admin/media` — paginated list, upload, view, metadata update, and safe delete using `media.*`. List accepts `search`, `type=image|application`, and `per_page` up to 100. Upload uses multipart field `file`; maximum 5 MiB; JPG/JPEG/PNG/WebP/PDF only.
- `/api/v1/admin/redirects` — list/create/update/delete using `redirects.*`. Source and target must be internal paths; status codes are 301, 302, 307, or 308.

Validation failures return HTTP 422, permission failures 403, missing records 404, and protected system-page or referenced-media deletion 409.

## Contributors

Public endpoints:

- `GET /api/v1/contributors` — accepts `search`, `discipline` slug, `featured=0|1`, `page`, and `per_page` up to 100.
- `GET /api/v1/contributors/{slug}` — active contributor detail with disciplines, media, public profile links, biography, and SEO.

Lists return featured profiles first, followed by `sort_order` and name. Private email, phone, editor IDs, and administrative metadata are never returned publicly.

Admin endpoints require the corresponding `contributors.*` permission:

- `GET|POST /api/v1/admin/contributors`
- `GET|PUT|PATCH|DELETE /api/v1/admin/contributors/{contributor}`
- `PATCH /api/v1/admin/contributors/{contributor}/status` — `{"is_active":false}`
- `PATCH /api/v1/admin/contributors/{contributor}/featured` — `{"is_featured":true}`
- `PATCH /api/v1/admin/contributors/reorder`
- `GET|PUT /api/v1/admin/contributors/{contributor}/seo` — existing `seo.view|update` permissions

Create accepts `name`, optional stable `slug`, profile fields, optional existing `media_id`, `discipline_ids`, and a `primary_discipline_id` contained in that list. LinkedIn and website URLs require HTTP/HTTPS. Biography HTML is sanitized. Delete deactivates the contributor to preserve future references.
# Phase 07 articles

- `GET /api/v1/articles` supports `search`, `discipline`, `category`, `topic`, `tag`, `featured`, `sort`, `page`, and `per_page`.
- `GET /api/v1/articles/{slug}` returns rich content, TOC, contributors, taxonomy, media, related articles, previous/next links, and SEO.
- Admin `/api/v1/admin/articles` supports CRUD plus `/{id}/status`, `/{id}/featured`, and `/{id}/seo`.

Allowed public sorts are `latest`, `oldest`, `a-z`, and `featured`. Only currently published articles are public.

# Phase 08 courses

- `GET /api/v1/courses` supports `search`, `discipline`, `level`, `mode`, `featured`, `sort`, `page`, and `per_page`.
- `GET /api/v1/courses/{slug}` returns course details, outcomes, curriculum, lessons, faculty, FAQs, related courses, enrollment configuration, and SEO.
- Admin `/api/v1/admin/courses` supports CRUD, status, featured state, and `/{id}/seo`.

# Phase 09 publications

- `GET /api/v1/publications` supports `search`, `discipline`, `type`, `category`, `access`, `featured`, `sort`, and pagination.
- `GET /api/v1/publications/{slug}` returns metadata, contributors, cover, safe preview, related records, and SEO without private full-file URLs.
- Admin `/api/v1/admin/publications` supports CRUD, status, featured state, and SEO.

# Phase 10 study resources

- `GET /api/v1/resources` supports `search`, `discipline`, `type`, `category`, `topic`, `tag`, `access`, `featured`, `sort`, `page`, and `per_page`.
- Allowed sorts are `latest`, `oldest`, `a-z`, and `featured`; access filters accept `free`, `login_required`/`login-required`, and `paid`.
- `GET /api/v1/resources/{slug}` returns taxonomy, thumbnail, safe preview, access requirements, related resources, and SEO. It never returns private disk or path information.
- Admin `/api/v1/admin/resources` supports list/create/detail/update/delete plus `/{resource}/status`, `/{resource}/featured`, and `/{resource}/seo`.
- Private resource files are uploaded through `POST /api/v1/admin/media` using multipart `storage=private`; this requires `resources.file.manage` and accepts PDF, DOC/DOCX, PNG/JPG/JPEG, and ZIP up to 50 MiB.

# Phase 11 digital access

- `GET /api/v1/resources/{slug}/download` — controlled resource delivery; free permits guests, login-required requires authentication, paid requires an active entitlement.
- `GET /api/v1/publications/{slug}/download` — equivalent publication delivery when a private full file exists.
- `GET /api/v1/account/digital-resources` — authenticated entitled library; supports `type`, `discipline`, `search`, and pagination.
- `GET /api/v1/account/downloads` — currently downloadable entitled content with customer-owned history counts.
- Admin `GET|POST /api/v1/admin/entitlements`, `GET /api/v1/admin/entitlements/{id}`, and `PATCH /api/v1/admin/entitlements/{id}/revoke` manage grants and revocation.

Downloads are limited to 20 requests per minute per authenticated user or IP. Responses never expose private disk paths. No payment or browser action creates a purchase entitlement.
# Phase 12 — Physical books

- `GET /api/v1/books` — filters: `search`, `discipline`, `category`, `author`, `publisher`, `min_price`, `max_price`, `featured`, `new_arrival`, `sort`, `page`, `per_page`.
- `GET /api/v1/books/{slug}` — book, ordered gallery, SEO, provisional inventory metadata, and related books.
- `GET /api/v1/authors`, `GET /api/v1/authors/{slug}`.
- `GET /api/v1/publishers`, `GET /api/v1/publishers/{slug}`.
- Admin CRUD: `/api/v1/admin/books`, `/api/v1/admin/authors`, `/api/v1/admin/publishers`.
- Admin book operations: `PATCH books/{id}/status`, `PATCH books/{id}/featured`, `PATCH books/{id}/new-arrival`, `PUT books/{id}/gallery`, and `GET|PUT books/{id}/seo`.

Prices are decimal INR strings. `saving_amount` and `discount_percentage` are calculated. Inventory is authoritative through Phase 13; persistent cart and server price verification belong to Phase 15.

# Phase 13 — Inventory

Public book responses expose `inventory.status`, `is_available`, and `is_low_stock`, without exact quantities. `GET /api/v1/books` accepts `availability=in-stock|low-stock|out-of-stock`.

Admin endpoints: `GET /admin/inventory`, `GET /admin/books/{book}/inventory`, `POST .../increase`, `POST .../decrease`, `POST .../adjust`, `PATCH .../threshold`, and `GET .../movements`. Mutations require a reason, run under row locks, invalidate catalogue caches, and return HTTP 409 for insufficient stock. Movements are immutable. Permissions use `inventory.view/adjust/increase/decrease/threshold.update/movements.view`.

# Phase 14 — Customer profile and addresses

Authenticated endpoints: `GET|PATCH /api/v1/account/profile`; `GET|POST /api/v1/account/addresses`; `GET|PUT|PATCH|DELETE /api/v1/account/addresses/{address}`; and `PATCH /api/v1/account/addresses/{address}/default`.

Profiles accept only name, unique email, and unique Indian mobile. Address types are `home`, `office`, and `other`; mobile is ten digits, PIN is six digits with a nonzero first digit, and country is `IN`. Address ownership is always derived from authentication. Requests for another customer's address return 404.

# Phase 16 cart coupons

`POST /api/v1/cart/coupon` applies one server-validated code to the resolved guest or customer cart. `DELETE /api/v1/cart/coupon` removes it. Cart summaries expose `eligible_coupon_subtotal`, `coupon_discount`, `discounted_subtotal`, and `payable_before_shipping`; no shipping or final grand total is calculated.

Admin coupon management is available at `/api/v1/admin/coupons` with standard list, create, show, update, status, and soft-delete actions protected by coupon permissions. See `COUPON_PROMOTION_ARCHITECTURE.md`.

# Phase 17 shipping

Customer endpoints are `POST /api/v1/shipping/serviceability`, `POST /api/v1/cart/shipping/quote`, and authenticated `POST /api/v1/checkout/shipping-options`. Clients send only a destination postal code or owned address ID and payment mode. Weight, dimensions, cart amount, discount, and shipping charge are server-derived.

Admin endpoints under `/api/v1/admin/shipping` manage settings, provider status, encrypted credentials, toggles, connection tests, priority, default provider, and pickup locations. Provider responses expose masked configuration only.

# Phase 18 checkout and orders

- `POST /api/v1/checkout/place-order` — public or authenticated COD checkout. Requires `shipping_quote_id`, `idempotency_key`, contact for guests, and either an owned `address_id` or `shipping_address`.
- `GET /api/v1/orders/{orderNumber}/success?token=...` — customer-owned or token-authorized confirmation.
- `GET /api/v1/account/orders` — authenticated order history with `status`, `search`, and pagination.
- `GET /api/v1/account/orders/{orderNumber}` — authenticated owned order detail.
- `GET /api/v1/admin/orders` — permission-gated operational list and filters.
- `GET /api/v1/admin/orders/{orderNumber}` — permission-gated detail.
- `PATCH /api/v1/admin/orders/{orderNumber}/status` — validated operational transition.

Client-provided prices, stock, discounts, tax, shipping charges, and totals are ignored.

# Phase 20 invoice endpoints

- `GET /api/v1/orders/{orderNumber}/invoice` - authorized customer/guest invoice metadata.
- `GET /api/v1/orders/{orderNumber}/invoice/download` - private PDF download.
- `GET|POST /api/v1/admin/invoice-settings` - view/update typed invoice settings; POST supports logo/signature multipart uploads.
- `GET /api/v1/admin/invoices` and `GET /api/v1/admin/invoices/{invoice}` - paginated list/detail.
- `POST /api/v1/admin/orders/{order}/invoice` - idempotent manual issuance for an eligible order.
- `GET /api/v1/admin/invoices/{invoice}/download` - protected admin PDF download.
- `POST /api/v1/admin/invoices/{invoice}/regenerate` - regenerate from the immutable invoice snapshot.
- `POST /api/v1/admin/invoices/{invoice}/void` - void with a required reason.


## Phase 21 shipment management

Shipment endpoints are available under `/api/v1/admin/shipments` for listing, detail, AWB, pickup, label, manifest, tracking refresh, reconciliation, retry, cancellation, and private document downloads. `POST /api/v1/admin/orders/{order}/shipment` books an eligible order. Customer tracking is `GET /api/v1/orders/{orderNumber}/tracking`; provider callbacks use `POST /api/v1/shipping/webhooks/{provider}`.

## Phase 22 notifications

Admin APIs under `/api/v1/admin/notifications` manage encrypted SMTP settings, connection tests, test email, templates, previews, delivery logs, and retries. Customers use `GET|PUT /api/v1/account/notification-preferences`. Events create deduplicated queued deliveries; `sent` means accepted by SMTP.
# Phase 23 operational APIs

Public routes: `POST /api/v1/contact`, `POST /api/v1/support`, `GET /api/v1/workshops`, `GET /api/v1/workshops/{slug}`, `POST /api/v1/workshops/{id}/registrations`, `GET /api/v1/careers`, `GET /api/v1/careers/{slug}`, and `POST /api/v1/careers/{id}/applications`. Mutation routes require a UUID `submission_token`; career and support upload requests use multipart form data.

Authenticated customers can list, view and reply to `/api/v1/account/support-tickets` and securely download their own attachments. Admin routes expose enquiry, ticket, workshop, registration, opening and application management under `/api/v1/admin`, protected by module permissions.

# Phase 24 discovery APIs
Public endpoints include `/notices`, `/gallery`, `/videos`, `/search`, and `/search/suggestions`, with slug detail routes and bounded filters. Admin notice, album/image and video management is under `/api/v1/admin` and permission protected. Gallery writes reference existing Media IDs. Search supports query, content type, discipline, relevance/latest sorting, global pagination and type facets.

