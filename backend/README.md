# E4ENGINEERS Laravel API

Laravel 13 REST API for the E4ENGINEERS React application, including customer authentication, administrative authorization, and engineering master data.

## Requirements

- PHP 8.3 or newer with OpenSSL, cURL, Mbstring, Fileinfo, PDO, PDO MySQL, and SQLite extensions
- Composer 2
- MySQL or MariaDB for shared development, staging, and production
- SQLite support for the isolated automated test suite

This workstation includes PHP 8.3 at `C:\php-8.3.15\php.exe`. Its command-line extensions must be enabled when running Laravel. PHP 8.2 is insufficient for Laravel 13.

## Environment

1. Copy `.env.example` to `.env`.
2. Run `php artisan key:generate`.
3. Set local MySQL/MariaDB values without committing credentials.
4. Create the configured development database without overwriting existing data.
5. Run `php artisan migrate` only after verifying the connected database.

The `.env.example` defaults describe MySQL. This workstation currently uses the local SQLite file as a Phase 01 fallback because no service is listening on MySQL port 3306. Tests always override this with SQLite `:memory:`.

## Run locally

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

- Backend: `http://localhost:8000`
- API base: `http://localhost:8000/api/v1`
- Health: `http://localhost:8000/api/v1/health`
- React frontend: `http://localhost:4177`

Use `localhost` in the browser for both applications. Mixing `localhost` and `127.0.0.1` can prevent Sanctum's session and CSRF cookies from being sent.

## Tests and quality

```powershell
php artisan test
vendor/bin/pint --format agent
```

The test environment uses in-memory SQLite, array cache/session/mail, and the synchronous queue driver. It never connects to staging or production. On this workstation, run the commands with `C:\php-8.3.15\php.exe` and an ini that enables the required extensions.

## API and authentication

- [API contract](docs/API.md)
- [Sanctum SPA authentication strategy](docs/AUTHENTICATION.md)
- [Frontend integration audit](BACKEND_INTEGRATION_AUDIT.md)

CORS origins are supplied through `CORS_ALLOWED_ORIGINS`; credentialed requests never use a wildcard origin. Phase 02 provides registration, login by email or mobile, logout, current-customer lookup, forgot-password, and reset-password endpoints under `/api/v1/auth`.

The React client must first request `/sanctum/csrf-cookie`, then send credentialed requests. Password reset mail uses the configured mail driver; local development writes reset messages to `storage/logs/laravel.log` when `MAIL_MAILER=log`.

## Administrative authorization

Seed the controlled roles and permissions safely and repeatedly:

```powershell
php artisan db:seed --class=AuthorizationSeeder
```

Create or explicitly promote the first Super Admin without storing credentials in source control:

```powershell
php artisan e4engineers:create-super-admin
```

Administrative login is `POST /api/v1/admin/auth/login`. Roles use lowercase kebab-case names; permissions use `resource.action`. Admin routes require Sanctum authentication, the `admin` middleware, and policy or permission authorization. The `super-admin` role bypasses permission checks centrally through `Gate::before`; the role itself and the final active Super Admin are protected.

See [ADMIN_ROLE_PERMISSION_MATRIX.md](ADMIN_ROLE_PERMISSION_MATRIX.md) for current assignments and [docs/API.md](docs/API.md) for endpoints.

Phase 05 CMS, media, settings, SEO, caching, upload restrictions, and frontend-consumption guidance are documented in [docs/CMS.md](docs/CMS.md).

Phase 06 contributor profiles, discipline/media relationships, privacy rules, filtering, caching, frontend integration, and future article/course relationship plans are documented in [docs/CONTRIBUTORS.md](docs/CONTRIBUTORS.md).

## Engineering master data

Phase 04 provides engineering disciplines, categories, topics, tags, course levels, resource types, and publication types. The standard database seeder is idempotent and installs both authorization and reference values:

```powershell
php artisan migrate
php artisan db:seed
```

Public APIs return only active records. Admin APIs support create, update, status changes, and ordering where applicable. Stable slugs are used as public identifiers; records are deactivated instead of hard-deleted. See [docs/API.md](docs/API.md) for routes and payloads.

## Queue and scheduler

Database queues are prepared by Laravel's standard jobs migration; local tests use `sync`. Production must run a supervised queue worker only after queued features are introduced. Laravel's scheduler requires one system cron entry calling `php artisan schedule:run` every minute when scheduled tasks are added; Phase 01 schedules no tasks.

## Architecture conventions

- Versioned controllers: `app/Http/Controllers/Api/V1`
- Form requests: `app/Http/Requests/Api/V1`
- API resources: `app/Http/Resources/Api/V1`
- Reusable response contract: `app/Support/ApiResponse.php`
- Complex domain operations belong in focused Services or Actions when those modules are implemented.
- Finite behavior-controlling statuses use PHP backed enums.
- Lowercase kebab-case slugs are stable public identifiers.
- Timestamps are stored consistently and presented using the configured `Asia/Kolkata` business timezone where required.

## Production foundation

Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS URLs, secure session cookies, trusted proxy behavior, database credentials, backups, and writable storage/log paths. Cache configuration/routes only after deployment environment values are final. Never log passwords, tokens, payment secrets, or full sensitive request bodies.
