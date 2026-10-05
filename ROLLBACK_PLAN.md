# Rollback plan

Do not use `php artisan migrate:rollback` as the only recovery method. Schema changes may be destructive or incompatible with data written by a newer release.

Before cutover, retain the previous application release, its frontend build, the exact environment configuration, and tested backups of the database plus public and private storage. Record backup time, restore owner, and the last safe data-write point. Keep the current DNS/hosting configuration available for reversal.

On a failed release: stop new checkout/payment writes or enter maintenance mode, pause queue workers and scheduled jobs, preserve application and webhook logs, and identify whether external payment or shipment events occurred. Restore the previous code and compatible database/storage snapshot together. Do not discard paid orders or shipment events that occurred after the snapshot; reconcile them manually against provider records before reopening writes. Restart workers against the restored release, clear/rebuild caches as needed, and run homepage, health, auth, cart, order-read, and admin smoke tests.

If a database restore would lose real transactions, keep the site in maintenance mode and repair forward instead. Reverse DNS only after confirming the target release and API are healthy. Document the incident and reconcile provider webhooks before resuming checkout.
