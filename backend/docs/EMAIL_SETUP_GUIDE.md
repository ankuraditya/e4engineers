# Email setup guide

1. Open `/admin/notifications` and select Email Settings.
2. Enter the SMTP host, port, username, and password.
3. Choose TLS, SSL, or None as required by the provider.
4. Enter the From Name, From Email, and optional Reply-To address.
5. Keep Queue Emails enabled for production.
6. Save the settings, test the connection, and send a test email to an address you control.
7. Enable Email Notifications after the test succeeds.
8. Review and enable the desired templates.

Run a persistent Laravel queue worker in production, such as `php artisan queue:work --tries=3`, under a process manager. Run `php artisan schedule:run` every minute. Review `failed_jobs` and the notification delivery log. Database SMTP takes precedence when enabled and complete; invalid database configuration does not silently fall back to environment SMTP.

Never place SMTP credentials in documentation, templates, logs, or frontend source.
