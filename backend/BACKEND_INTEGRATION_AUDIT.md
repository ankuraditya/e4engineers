# E4ENGINEERS Frontend to Backend Integration Audit

Audit updated: 2026-10-01. APIs are **planned** unless explicitly identified as implemented.

| Frontend module | React route | Current data/state source | Future backend module | Planned API |
|---|---|---|---|---|
| Homepage | `/` | Component-local arrays; frontend integration deferred | **Backend-ready:** system page, banners, settings, SEO | `GET /api/v1/pages/home`; `GET /api/v1/banners?placement=homepage-hero` |
| Engineering disciplines | `/engineering` | `phaseOneData.js`; frontend integration deferred | **Implemented master data** | `GET /api/v1/engineering-disciplines` |
| Discipline detail | `/engineering/:slug` | `phaseOneData.js`; frontend integration deferred | **Implemented master data** | `GET /api/v1/engineering-disciplines/{slug}` |
| Articles | `/articles` | **Integrated:** `articlesService`, server filtering/pagination | **Implemented articles module** | `GET /api/v1/articles` |
| Article detail | `/articles/:slug` | **Integrated:** article detail API | **Implemented articles module** | `GET /api/v1/articles/{slug}` |
| Courses | `/courses` | **Integrated:** `coursesService`, server filtering | **Implemented courses module** | `GET /api/v1/courses` |
| Course detail/enquiry | `/courses/:slug` | **Integrated:** course detail API; CTA configuration from backend | **Implemented course presentation** | `GET /api/v1/courses/{slug}` |
| Publications | `/publications` | **Integrated:** `publicationsService`, server filtering | **Implemented publications module** | `GET /api/v1/publications` |
| Publication detail | `/publications/:slug` | **Integrated:** publication detail API | **Implemented publications module** | `GET /api/v1/publications/{slug}` |
| Study resources | `/resources` | `phaseTwoData.js`, client filtering | Resources | `GET /api/v1/public/resources` |
| Resource detail/access | `/resources/:slug` | `phaseThreeData.js`, placeholder access | Resources/Entitlements | `GET /api/v1/public/resources/{slug}`; `POST /api/v1/account/resources/{id}/access` |
| Books | `/books` | `phaseThreeData.js`, component arrays | Books/Inventory | `GET /api/v1/public/books` |
| Book detail | `/books/:slug` | `phaseThreeData.js` | Books/Inventory | `GET /api/v1/public/books/{slug}` |
| Universal search | `/search` | Combined local arrays and client filtering | Search | `GET /api/v1/public/search` |
| Contact | `/contact` | Form remains frontend-only; display integration deferred | **Backend-ready display settings**; enquiry persistence planned | `GET /api/v1/settings/public`; future `POST /api/v1/public/enquiries` |
| Login | `/login` | **Integrated:** Sanctum session via `AuthContext` | Authentication | `POST /api/v1/auth/login` |
| Registration | `/register` | **Integrated:** Sanctum session via `AuthContext` | Authentication | `POST /api/v1/auth/register` |
| Forgot password | `/forgot-password` | **Integrated:** Laravel password broker | Authentication | `POST /api/v1/auth/forgot-password` |
| Reset password | `/reset-password` | **Integrated:** Laravel reset token and React reset URL | Authentication | `POST /api/v1/auth/reset-password` |
| Cart | `/cart` | `CartContext`, `localStorage` | Cart/Pricing | `GET/POST/PATCH/DELETE /api/v1/cart...` |
| Checkout | `/checkout` | Cart/customer state and simulated order | Checkout | `POST /api/v1/checkout/quote`; `POST /api/v1/checkout/orders` |
| Order success | `/order-success/:number` | `sessionStorage` development order | Orders | `GET /api/v1/account/orders/{number}` |
| Customer dashboard | `/account` | Static/mock account summaries | Account | `GET /api/v1/account/dashboard` |
| Profile | `/account/profile` | `CustomerContext`, `localStorage` | Account | `GET/PATCH /api/v1/account/profile` |
| Addresses | `/account/addresses` | `CustomerContext`, `localStorage` | Addresses | `GET/POST/PATCH/DELETE /api/v1/account/addresses...` |
| Orders | `/account/orders` | `phaseSixData.js` | Orders | `GET /api/v1/account/orders` |
| Order detail | `/account/orders/:number` | `phaseSixData.js` plus session demo | Orders | `GET /api/v1/account/orders/{number}` |
| Tracking | `/account/orders/:number/track` | Mock shipment/timeline data | Shipments | `GET /api/v1/account/orders/{number}/tracking` |
| Digital resources | `/account/digital-resources` | `phaseSixData.js` entitlements | Entitlements | `GET /api/v1/account/resources` |
| Downloads | `/account/downloads` | `phaseSixData.js`; `downloadService` denies | Protected downloads | `GET /api/v1/account/downloads`; `POST /api/v1/account/downloads/{id}` |
| Payment history | `/account/payments` | `phaseSevenData.js` | Payments | `GET /api/v1/account/payments` |
| Account settings | `/account/settings` | `accountService`, `localStorage` | Preferences | `GET/PATCH /api/v1/account/preferences` |
| Change password | `/account/change-password` | Simulated success response | Authentication | `PUT /api/v1/account/password` |
| FAQ | `/faq` | `phaseSevenData.js` | CMS/FAQ | `GET /api/v1/public/faqs` |
| Contributors | `/contributors` | **Integrated:** `contributorsService`, server search/filtering, loading/error/empty states | **Implemented contributor module** | `GET /api/v1/contributors` |
| Notices | `/notices` | `phaseEightData.js`, client filtering | Notices | `GET /api/v1/public/notices` |
| Workshops | `/workshops` | `phaseEightData.js`; enquiry link | Workshops/Registrations | `GET /api/v1/public/workshops`; `POST /api/v1/public/workshop-registrations` |
| Gallery | `/gallery` | `phaseEightData.js` | Gallery | `GET /api/v1/public/gallery` |
| Videos | `/videos` | `phaseEightData.js`, placeholder player | Videos | `GET /api/v1/public/videos` |
| Support | `/support` | Client validation and success message only | Support requests | `POST /api/v1/account/support-requests` |
| Careers | `/careers` | `phaseNineData.js`, frontend-only application | Careers | `GET /api/v1/public/careers`; `POST /api/v1/public/career-applications` |
| Legal pages | `/privacy-policy`, `/terms`, `/shipping-policy`, `/returns-refunds` | Existing static fallback; frontend integration deferred | **Backend-ready CMS pages** | `GET /api/v1/pages/{slug}` |

## Major replacement areas

- Catalog/content arrays: `src/data/phaseOneData.js` through `phaseNineData.js` and homepage component arrays.
- Browser persistence: cart, customer profile/addresses, and preferences use `localStorage`; development checkout uses `sessionStorage`.
- Authentication: customer registration, login, session restoration, logout, forgot password, and reset password are integrated; remaining account operations are placeholders.
- Commerce: prices, totals, payment selection, order creation, order history, and tracking are mock or browser-calculated.
- Digital access: entitlement/download UI is mocked; the download service intentionally refuses access until Laravel authorization exists.
- Forms: contact, support, careers, course enquiries, and workshop registrations validate locally but are not submitted.

## Contract conventions

- Slugs are lowercase kebab-case and treated as stable public identifiers.
- Lists use `page`, `per_page`, `search`, domain-specific filters such as `discipline`, and allow-listed `sort` values. Default page size is 12; maximum is 100.
- Successful responses use `success`, `message`, `data`, and optional `meta`; errors use `success`, `message`, and `errors`.
- Soft deletes will be considered for recoverable editorial records (articles, books, courses, publications, resources, contributors), based on audit/recovery needs. Financial and order history must remain immutable/auditable rather than relying on blind soft deletion.
- Business states should use PHP backed enums when the finite state controls behavior. Controllers remain thin; multi-step rules belong in Services or focused Actions.

## Phase 02 integration status

Integrated: registration, login by email/mobile, logout, current user restoration, forgot password, reset password, protected-route redirects, redirect-after-login, and authenticated header account routing.

Still frontend-only: profile editing, addresses, preferences, authenticated change password, cart, checkout/order creation, orders, tracking, payments, downloads/entitlements, and all content/CMS modules.

## Phase 03 administrative infrastructure status

- Admin Authentication: **integrated backend foundation** — dedicated login, logout, current administrator, throttling, status enforcement, security logging.
- Roles: **implemented** — nine seeded system roles, protected critical roles, listing and controlled custom-role management.
- Permissions: **implemented** — controlled Phase 03 catalogue, role assignments, central Super Admin bypass, permission cache invalidation.
- Admin CRUD foundation: **implemented** — list, create, view, update identity/roles, and change status with escalation and final-Super-Admin safeguards.
- Admin frontend: **not implemented**; customer React pages were unchanged.
- CMS, articles, courses, publications, resources, books, inventory, orders, and payments remain **planned**.

## Phase 04 engineering master-data status

- **Implemented:** engineering disciplines, categories, topics, tags, course levels, resource types, and publication types, with public active lists and permission-protected admin management.
- **Implemented:** stable slugs, active/inactive transitions, ordered masters, category-context filtering, topic discipline filtering, idempotent seeds, and cache invalidation.
- **Deletion strategy:** hard-delete API routes are absent. Reference records are retired with `is_active=false` so future content relationships remain valid.
- **Frontend integration:** deferred. Existing pages still use local Phase data because Phase 04 does not add article, course, resource, publication, or book content APIs; changing only filter masters would create a mixed and fragile source of truth.

## Phase 05 CMS and website-foundation status

- **Backend-ready:** CMS pages and sections, protected system routes, typed public settings, contact display values, social links, scheduled banners, media library, page SEO, and internal redirect records.
- **Frontend integration deferred:** approved React layouts and current static content remain unchanged. A later focused integration can map structured CMS fields without mixing content sources or risking staging regressions.
- **Media:** portable Storage URLs, 5 MiB validation, safe file types, image metadata, permission enforcement, and reference-protected deletion.
- **SEO limitation:** metadata APIs are complete, while the SPA remains client-rendered. Production crawl requirements may later justify prerendering or SSR without changing the current React framework now.
- **Still planned:** contact-form persistence, articles, courses, publications/resources business records, books, inventory, and commerce.

## Phase 06 contributor integration status

- **Integrated:** `/contributors` now loads contributor and engineering-discipline data from Laravel through `contributorsService`.
- **Implemented:** public list/detail APIs, search, discipline and featured filters, pagination, media, multi-discipline profiles, stable slugs, SEO, admin management, permissions, status, featured toggle, reorder, privacy controls, and cache invalidation.
- **Mock data removed:** the old named prototype contributor array and fake article associations were removed from `phaseSevenData.js`.
- **Development data:** local/testing environments may seed clearly labelled prototype profiles; production receives no invented people.
- **Still planned:** contributor/article relations in Phase 07 and course/faculty relations in Phase 08.

## Phase 07 article integration status

- **Integrated:** article listing, article detail, Homepage Featured Knowledge, taxonomy filters, contributor authors, media, SEO, TOC, related articles, and previous/next navigation.
- **Implemented:** publication workflow, sanitized technical HTML, safe formula source, tables, diagrams through media, reading-time calculation, public/admin APIs, permissions, and cache invalidation.
- **Mock data removed:** article records and hardcoded article-detail content were removed from `phaseOneData.js` and `PhaseOnePages`; the listing, detail, homepage, and discipline article sections now use Laravel.
- **Still planned:** Courses in Phase 08 and universal cross-module search in a later focused phase.

## Phase 08 course integration status

- **Integrated:** course listing, course detail, Homepage Featured Courses, filters, curriculum, faculty, FAQ, enrollment CTA configuration, and related courses.
- **Implemented:** course workflow, structured duration and fees, normalized outcomes/modules/lessons, contributor faculty, media, SEO, permissions, and caching.
- **Scope:** no enrollment persistence, progress tracking, assessments, certificates, streaming, or other LMS behavior.

## Phase 09 publication integration status

- **Integrated:** publication listing, detail, Homepage Journals & Publications, discipline publications, filters, cover presentation, access/price metadata, previews, contributors, related records, and SEO.
- **Security boundary:** no permanent full-file URLs, entitlements, purchase completion, or secure downloads. Those remain Phase 11 work.
- **Architecture:** one publication record represents one issue/document, matching the approved frontend.
# Phase 10 integration

- Study Resources Listing: Laravel `/api/v1/resources` integrated.
- Study Resource Detail: Laravel `/api/v1/resources/{slug}` integrated.
- Homepage Study Resources: existing Resource Type navigation cards integrated with `/api/v1/resource-types`.
- Engineering Discipline Resource section: integrated through the discipline filter.
- Account Digital Resources and Account Downloads: intentionally retained for Phase 11 entitlement and secure-download integration.

# Phase 11 integration

- My Digital Resources: authenticated entitlement API integrated.
- My Downloads: authenticated availability and history API integrated.
- Resource and Publication secure access: controlled, rate-limited Laravel streaming integrated.
- Manual admin entitlement grant/revoke: integrated.

# Phase 12 integration

Books listing, book detail, homepage featured books, discipline filtering, and related books now consume the Laravel catalogue API. Authors, publishers, pricing, cover/gallery media, and SEO are backend managed. Inventory, persistent cart, checkout, orders, and payments remain pending by phase boundary.

# Phase 13 integration

Book availability, derived inventory status, catalogue availability filters, stock-aware detail/actions, and admin stock APIs are integrated. Persistent Cart, checkout reservation, order deduction, cancellation restoration, and return restoration remain pending.

# Phase 14 integration

Customer Profile, My Addresses, Checkout contact prefill, default selection, and Checkout address creation now use the authenticated Laravel account APIs and one shared React state source. Persistent Cart, shipping calculation, order creation, address snapshots, and payments remain pending.

# Phase 23 integration

Contact, support, workshops and careers now use persistent APIs. Contact and support frontend forms submit to Laravel, career openings load dynamically and applications upload resumes privately. Notifications reuse the Phase 22 notification manager. Notices, gallery, videos, global search and deployment remain outside this phase.

# Phase 24 integration
Notices, homepage latest updates, gallery, videos, universal search and header suggestions now use Laravel APIs. Search includes only an explicit public-content registry. Phase 05 Media and SEO infrastructure are reused. Production deployment and Phase 25 audit work were not started.

# Phase 25 audit in progress (2026-10-04)

The local SQLite suite passes 169 tests with 841 assertions. The React suite passes 36 tests, Sites packaging passes four tests, and the production frontend build succeeds. A route-list review found 373 API routes, including 275 admin routes; all admin routes except login carry `EnsureAdminUser`. Granular permissions, private-file access, webhook replay, provider security, and concurrency require a separate final review.

Frontend Phase 25 fixes connect published CMS banner/legal content, remove active demo records from homepage/discovery/careers, correct catalogue and resource links, and show a real 404 state. Production-like MariaDB/MySQL testing, staging UAT, payment/courier sandbox E2E, queue/scheduler operation, approved legal content, complete file attachments, mobile/accessibility/performance/SEO review, and deployment remain open. Do not treat this as a completion or go-live sign-off.

