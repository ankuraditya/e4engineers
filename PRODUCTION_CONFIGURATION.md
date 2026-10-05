# Production configuration

Status: preparation only. The verified local backend uses SQLite, `APP_ENV=local`, and `APP_DEBUG=true`. Do not copy its `.env` to production.

Configure a private Laravel environment with `APP_ENV=production`, `APP_DEBUG=false`, a stable generated `APP_KEY`, the final HTTPS `APP_URL` and `FRONTEND_URL`, a production MariaDB/MySQL database, and backups. Set the exact frontend origin in `CORS_ALLOWED_ORIGINS`, the deployed host in `SANCTUM_STATEFUL_DOMAINS`, and secure session cookies (`SESSION_SECURE_COOKIE=true`, appropriate `SESSION_DOMAIN` and `SESSION_SAME_SITE`). Test sign-in and CSRF after configuration.

Route `/api/v1` and `/sanctum/csrf-cookie` to Laravel; the SPA fallback must not return HTML for API requests. Keep Laravel's `storage`, `.env`, logs, database backups, and private uploads outside the public document root. Serve public media only through the intended public disk and verify private downloads require authorization.

Use a production queue worker for `QUEUE_CONNECTION=database` or an approved alternative. Run `php artisan schedule:run` every minute; `payments:expire-attempts` and `shipments:sync-tracking` are scheduled every five minutes in `backend/routes/console.php`. Provide worker restart, failed-job review, and log monitoring procedures.

The administrator must enter business-approved invoice details, sender email/SMTP, pickup address, shipping rates and providers, COD policy, and payment gateway test/live credentials in the admin UI. Keep all credentials out of source control and documentation. Do not enable a live gateway or create a live shipment until its callback, webhook, reconciliation, and failure paths pass staging tests.

Production analytics, domain/DNS, TLS certificate, backup destination, provider accounts, and monitoring destination are not verified in this repository. Record their approved values in the deployment environment, not here.
