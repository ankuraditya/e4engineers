# API audit

Status: partial. The Laravel API is versioned under `/api/v1`; the local `/api/v1/health` and public catalogue endpoints respond. The 169-test SQLite PHPUnit suite covers authentication, RBAC, CMS, publishing, cart, checkout, payment, shipping, invoices, shipments, notifications, operations, and discovery. This is evidence for tested paths, not a manual review of every endpoint.

The public CMS exposes published pages, public settings, and active banners. Phase 25 frontend changes now read the homepage banner and populated legal-page content. The approved static legal layout remains the fallback when CMS content is empty. Confirm published content updates and cache invalidation through admin UAT.

Backend route definitions are in `backend/routes/api.php`; endpoint-specific validation and authorization must be compared against every route in the final security pass. Customer order isolation has a dedicated feature test. Webhook handlers, provider credentials, private file access, rate limits, and IDOR boundaries still need production-like staging review.

`artisan route:list --path=api/v1 --json` reported 373 API routes, including 275 under `/api/v1/admin`. All admin routes except the intended login endpoint carried the `EnsureAdminUser` middleware in this route-list review. This does not prove each action has the correct granular permission; controller and role-matrix review remains open.

Open validation: full MariaDB/MySQL suite, API error contract and pagination on all modules, request-size limits, webhook replay, customer role matrix, queue/scheduler behavior, and provider test-mode callback paths.
