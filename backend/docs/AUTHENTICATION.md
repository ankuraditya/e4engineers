# Authentication Strategy

E4ENGINEERS uses Laravel Sanctum SPA cookie authentication for the first-party React frontend. Phase 02 implements registration, login, logout, current-user restoration, forgot password, and password reset. It does not use bearer tokens or localStorage as authentication authority.

## Local origins

- React: `http://localhost:4177`
- Laravel: `http://localhost:8000`
- API: `http://localhost:8000/api/v1`

Both local URLs intentionally use the `localhost` hostname so browser CSRF and session cookies remain same-site across ports. Laravel may still bind its server to `127.0.0.1`.

The React API client first requests `/sanctum/csrf-cookie`, reads the URL-decoded `XSRF-TOKEN` cookie, and sends it as `X-XSRF-TOKEN` with credentialed write requests.

## Staging preparation

The frontend origin `https://advom.uiprocorp.com` is allow-listed. Before staging auth is deployed, provide an HTTPS Laravel API URL and configure `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE=true`, and the final SameSite policy together. No production secrets are stored here.

## Password reset

Laravel's password broker creates and validates reset tokens. Reset notifications point to the React route `${FRONTEND_URL}/reset-password?token=...&email=...`. Local mail uses Laravel's log mailer; inspect `storage/logs/laravel.log` for the reset link when testing manually. Responses do not disclose whether an email exists.

## Administrative sessions

Phase 03 uses the same first-party Sanctum session guard and `users` table. A dedicated admin login endpoint additionally requires an active account and one of the configured administrative roles. Admin routes enforce both administrative identity and endpoint permissions. Super Admin permission bypass is centralized with `Gate::before`.

## Deferred items

Email verification is architecture-ready but not enforced. SMS/mobile OTP, profile editing, address persistence, account settings, authenticated password change, admin frontend UI, CMS, and business modules remain deferred.
