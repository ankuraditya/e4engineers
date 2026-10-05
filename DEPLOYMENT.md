# Full-system deployment plan

Status: plan only; no Phase 25 production deployment has been performed. The earlier cPanel upload was a frontend demo and does not prove Laravel commerce deployment.

1. Provision a staging host with PHP 8.3-compatible extensions, Composer dependencies, the intended MariaDB/MySQL version, HTTPS, persistent private/public storage, a queue worker, and cron. Verify the host can run Laravel and route the React client plus `/api/v1` and Sanctum correctly. Shared hosting limitations must be resolved before choosing cPanel as the production target.
2. Create private environment variables per `PRODUCTION_CONFIGURATION.md`. Never upload local `.env`, test database, provider secrets, or development logs.
3. Deploy the backend release without replacing persistent storage. Install locked dependencies, run configuration checks, back up the database, and apply reviewed migrations. Create the initial super admin with the existing `e4engineers:create-super-admin` command through a secure interactive process.
4. Run the backend test suite on staging against MariaDB/MySQL. Configure and start queue workers and the one-minute scheduler. Confirm `/api/v1/health`, session cookies, CSRF, storage permissions, and logs.
5. Build the frontend from the same release with the correct API origin. Run `npm test`, `npm run test:sites`, and `npm run build`; deploy `frontend/dist/client` with API routing taking precedence over SPA fallback.
6. Configure admin-controlled business data, test-mode integrations, and webhooks. Run `UAT_CHECKLIST.md`. Obtain client approval for legal content and final business settings.
7. Record a backup and tested rollback point, then request an explicit cutover decision. Only after authorization, update DNS/production routing and perform the smoke tests in `GO_LIVE_CHECKLIST.md`.

Post-deploy smoke: homepage, deep route, API health, register/login/logout, book/cart, checkout quote, contact, search, admin login, media, queue, scheduler, and logs. Do not run a real charge or courier booking without separate authorization.
